<?php


namespace CoreShop\Payum\MollieBundle\Extension;

use CoreShop\Component\Core\Model\OrderItemInterface as CoreOrderItemInterface;
use CoreShop\Component\Order\Model\AdjustmentInterface;
use CoreShop\Component\Order\Model\CartPriceRuleInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\OrderItemInterface;
use CoreShop\Component\Order\Model\ProposalCartPriceRuleItemInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Product\Model\ProductInterface;
use Mollie\Api\Types\OrderLineType;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Tool;

class ConvertOrderItemsExtension extends AbstractConvertOrderExtension
{


    public function __construct(protected int $decimalFactor, protected int $decimalPrecision)
    {

    }

    /**
     * @inheritDoc
     */
    protected function doPostExecute(PaymentInterface $payment, OrderInterface $order, array $result = []): array
    {
        $lineItems = [];

        foreach ($order->getItems() as $item) {
            $lineItems[] = $this->transformOrderItemToLineItem($item, $payment->getCurrencyCode(), $order->getLocaleCode());
        }

        if ($order->hasPriceRules()) {
            foreach ($order->getPriceRuleItems()->getItems() as $priceRuleItem) {
                $priceRuleItemTransformed = $this->transformPriceRuleItemToLineItem($priceRuleItem, $payment->getCurrencyCode(), $order->getLocaleCode());

                if ($priceRuleItemTransformed === false) {
                    continue;
                }

                $lineItems[] = $priceRuleItemTransformed;
            }
        }

        if ($order->getShipping() > 0) {
            $shippingItems = $this->transformShippingToLineItem($order, $payment->getCurrencyCode());

            $lineItems = array_merge($lineItems, $shippingItems);
        }

        foreach ($order->getAdjustments() as $adjustment) {
            if ($adjustment->getNeutral()) {
                continue;
            }

            if (in_array($adjustment->getTypeIdentifier(), [AdjustmentInterface::SHIPPING, AdjustmentInterface::CART_PRICE_RULE])) {
                continue;
            }

            $lineItems[] = $this->transformAdjustmentToLineItem($adjustment, $payment->getCurrencyCode());
        }

        $result['lines'] = $lineItems;

        return $result;
    }


    /**
     * @param AdjustmentInterface $adjustment
     * @param $currencyCode
     *
     * @return array
     */
    protected function transformAdjustmentToLineItem(AdjustmentInterface $adjustment, $currencyCode): array
    {
        $amountGross = $adjustment->getAmount(true);
        $amountNet = $adjustment->getAmount(false);

        $vatAmount = $amountGross - $amountNet;
        $vatRate = round(($vatAmount / $amountNet) * 100);

        // needed to prevent rounding errors of Mollie
        $vatAmount = round($amountGross * ($vatRate / (100 + $vatRate)));

        return [
            'type' => OrderLineType::TYPE_SURCHARGE,
            'name' => $adjustment->getTypeIdentifier(),
            'quantity' => 1,
            'unitPrice' => $this->transformMoneyWithCurrency($amountGross, $currencyCode),
            'totalAmount' => $this->transformMoneyWithCurrency($amountGross, $currencyCode),
            'vatAmount' => $this->transformMoneyWithCurrency($vatAmount, $currencyCode),
            'vatRate' => sprintf("%01.2f", $vatRate),
            'metadata' => json_encode([
                'typeIdentifier' => $adjustment->getTypeIdentifier(),
            ])
        ];
    }

    protected function transformPriceRuleItemToLineItem(ProposalCartPriceRuleItemInterface $priceRuleItem, string $currencyCode, string $localeCode): bool|array
    {
        $discountGross = $priceRuleItem->getDiscount();
        $discountNet = $priceRuleItem->getDiscount(false);

        if ($discountNet == 0) {
            return false;
        }

        $vatAmount = $discountGross - $discountNet;
        $vatRate = round(($vatAmount / $discountNet) * 100);

        return [
            'type' => OrderLineType::TYPE_DISCOUNT,
            'name' => empty($priceRuleItem->getVoucherCode()) ? $priceRuleItem->getCartPriceRule()->getLabel($localeCode) : sprintf("%s voucher code", $priceRuleItem->getVoucherCode()),
            'quantity' => 1,
            'unitPrice' => $this->transformMoneyWithCurrency($discountGross, $currencyCode),
            'totalAmount' => $this->transformMoneyWithCurrency($discountGross, $currencyCode),
            'vatAmount' => $this->transformMoneyWithCurrency($vatAmount, $currencyCode),
            'vatRate' => sprintf("%01.2f", $vatRate),
            'metadata' => json_encode([
                'voucherCode' => $priceRuleItem->getVoucherCode(),
                'cartPriceRulePimcoreId' => $priceRuleItem->getCartPriceRule() instanceof CartPriceRuleInterface ? $priceRuleItem->getCartPriceRule()->getId() : null,
                'typeIdentifier' => AdjustmentInterface::CART_PRICE_RULE
            ])
        ];
    }


    protected function transformShippingToLineItem(OrderInterface $order, string $currencyCode): array
    {
        $shippingGross = $order->getShipping(true);
        $vatAmount = $order->getShippingTax();
        $vatRate = $order->getShippingTaxRate();

        return [
            [
                'type' => OrderLineType::TYPE_SHIPPING_FEE,
                'name' => 'Shipping Costs',
                'quantity' => 1,
                'unitPrice' => $this->transformMoneyWithCurrency($shippingGross, $currencyCode),
                'totalAmount' => $this->transformMoneyWithCurrency($shippingGross, $currencyCode),
                'vatAmount' => $this->transformMoneyWithCurrency($vatAmount, $currencyCode),
                'vatRate' => sprintf("%01.2f", $vatRate),
                'metadata' => json_encode([
                    'typeIdentifier' => AdjustmentInterface::SHIPPING
                ])
            ]
        ];
    }

    /**
     * @param OrderItemInterface $orderItem
     * @param $currencyCode
     * @param null $locale
     *
     * @return array
     */
    protected function transformOrderItemToLineItem(OrderItemInterface $orderItem, string $currencyCode, string $locale = null): array
    {
        $product = $orderItem->getProduct();
        $itemTotal = $orderItem->getTotal(true);
        $itemPrice = round($itemTotal / $orderItem->getQuantity()); // because of adjustments that could also be on item level
        $taxAmount = $orderItem->getTotalTax();
        $taxRate = round(($taxAmount / $orderItem->getTotal(false)) * 100);


        $lineItem = [
            'name' => $orderItem->getName($locale),
            'type' => OrderLineType::TYPE_PHYSICAL,
            'quantity' => $orderItem->getQuantity(),
            'unitPrice' => $this->transformMoneyWithCurrency($itemPrice, $currencyCode),
            'totalAmount' => $this->transformMoneyWithCurrency($itemTotal, $currencyCode),
            'vatRate' => sprintf("%01.2f", $taxRate),
            'vatAmount' => $this->transformMoneyWithCurrency($taxAmount, $currencyCode),
        ];

        if ($orderItem instanceof CoreOrderItemInterface) {
            $lineItem['type'] = $orderItem->getDigitalProduct() === true ? OrderLineType::TYPE_DIGITAL : OrderLineType::TYPE_PHYSICAL;
        }

        if ($product instanceof ProductInterface) {
            $lineItem['sku'] = $product->getSku();

            $linkGenerator = $orderItem->getClass()->getLinkGenerator();

            if ($linkGenerator instanceof LinkGeneratorInterface) {
                $lineItem['productUrl'] = Tool::getHostUrl() . $linkGenerator->generate($orderItem, [
                        '_locale' => $locale
                    ]);
            }
        }

        $lineItem['metadata'] = json_encode([
            'orderItemPimcoreId' => $orderItem->getId()
        ]);

        return $lineItem;
    }


    /**
     * @param int $amount
     * @param string $currencyCode
     *
     * @return string[]
     */
    protected function transformMoneyWithCurrency(int $amount, string $currencyCode): array
    {
        $value = (int)round((round($amount / $this->decimalFactor, $this->decimalPrecision) * 100), 0);

        return [
            'value' => sprintf("%01.2f", ($value / 100)),
            'currency' => $currencyCode
        ];
    }
}
