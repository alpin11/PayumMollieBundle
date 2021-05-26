<?php


namespace CoreShop\Payum\MollieBundle\Extension;

use CoreShop\Component\Order\Model\AdjustmentInterface;
use CoreShop\Component\Order\Model\CartPriceRuleInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\OrderItemInterface;
use CoreShop\Component\Core\Model\OrderItemInterface as CoreOrderItemInterface;
use CoreShop\Component\Order\Model\ProposalCartPriceRuleItemInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Pimcore\Templating\Helper\LinkGeneratorHelperInterface;
use CoreShop\Component\Product\Model\ProductInterface;
use CoreShop\Component\Taxation\Model\TaxItemInterface;
use Mollie\Api\Types\OrderLineType;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Tool;

class ConvertOrderItemsExtension extends AbstractConvertOrderExtension
{
    /**
     * @var int|float
     */
    protected $decimalFactor;
    /**
     * @var LinkGeneratorHelperInterface
     */
    protected $linkGeneratorHelper;

    public function __construct(LinkGeneratorHelperInterface $linkGeneratorHelper, $decimalFactor)
    {
        $this->decimalFactor = $decimalFactor;
        $this->linkGeneratorHelper = $linkGeneratorHelper;
    }

    /**
     * @inheritDoc
     */
    protected function doPostExecute(PaymentInterface $payment, OrderInterface $order, $result = [])
    {
        $lineItems = [];

        foreach ($order->getItems() as $item) {
            $lineItems[] = $this->transformOrderItemToLineItem($item, $payment->getCurrencyCode(), $order->getLocaleCode());
        }

        if ($order->hasPriceRules()) {
            foreach ($order->getPriceRuleItems()->getItems() as $priceRuleItem) {
                $lineItems[] = $this->transformPriceRuleItemToLineItem($priceRuleItem, $payment->getCurrencyCode(), $order->getLocaleCode());
            }
        }

        if ($order->getShipping() > 0) {
            $shippingItems = $this->transformShippingToLineItem($order, $payment->getCurrencyCode());

            $lineItems = array_merge($lineItems, $shippingItems);
        }

        foreach ($order->getAdjustments() as $adjustment) {
            if ($adjustment->getNeutral() == true) {
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
    protected function transformAdjustmentToLineItem(AdjustmentInterface $adjustment, $currencyCode)
    {
        $amountGross = $adjustment->getAmount(true);
        $amountNet = $adjustment->getAmount(false);

        $vatAmount = $amountGross - $amountNet;
        $vatRate = round(($vatAmount / $amountNet) * 100);

        // TODO: needed to prevent rounding errors of mollie
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

    /**
     * @param ProposalCartPriceRuleItemInterface $priceRuleItem
     * @param $currencyCode
     * @param $localeCode
     *
     * @return array
     */
    protected function transformPriceRuleItemToLineItem(ProposalCartPriceRuleItemInterface $priceRuleItem, $currencyCode, $localeCode)
    {
        $discountGross = $priceRuleItem->getDiscount(true);
        $discountNet = $priceRuleItem->getDiscount(false);

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


    /**
     * @param OrderInterface $order
     * @param $currencyCode
     * @param $localeCode
     *
     * @return array
     */
    protected function transformShippingToLineItem(OrderInterface $order, $currencyCode)
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
    protected function transformOrderItemToLineItem(OrderItemInterface $orderItem, $currencyCode, $locale = null)
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

            $image = $product->getImage();

            if ($image instanceof Asset\Image) {
                $imageUrl = $image->getThumbnail([
                    'width' => 150,
                    'aspectratio' => true,
                    'format' => 'png',
                    'quality' => 80
                ])->getPath();

                // if not valid url we only have the path to the thumbnail
                // so we need to prepend the host url
                if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $imageUrl = Tool::getHostUrl() . $imageUrl;
                }

                // only set imageUrl if its valid
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $lineItem['imageUrl'] = $imageUrl;
                };
            }

            $lineItem['productUrl'] = Tool::getHostUrl() . $this->linkGeneratorHelper->getPath($product, null, [
                    '_locale' => $locale
                ]);
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
    protected function transformMoneyWithCurrency(int $amount, string $currencyCode)
    {
        return [
            'value' => sprintf("%01.2f", ($amount / $this->decimalFactor)),
            'currency' => $currencyCode
        ];
    }
}
