<?php


namespace CoreShop\Payum\MollieBundle\Extension;

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

        foreach ($order->getPriceRules() as $priceRuleItem) {
            $lineItems[] = $this->transformPriceRuleItemToLineItem($priceRuleItem, $payment->getCurrencyCode(), $order->getLocaleCode());
        }

        if ($order->getShipping() > 0) {
            $shippingItems = $this->transformShippingToLineItem($order, $payment->getCurrencyCode());

            $lineItems = array_merge($lineItems, $shippingItems);
        }

        $result['lines'] = $lineItems;

        return $result;
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
                'cartPriceRulePimcoreId' => $priceRuleItem->getCartPriceRule() instanceof CartPriceRuleInterface ? $priceRuleItem->getCartPriceRule()->getId() : null
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
        $vatAmount = round($order->getShippingTax() / $this->decimalFactor);
        $vatRate = $order->getShippingTaxRate();

        return [
            [
                'type' => OrderLineType::TYPE_SHIPPING_FEE,
                'name' => 'shipping costs',
                'quantity' => 1,
                'unitPrice' => $this->transformMoneyWithCurrency($shippingGross, $currencyCode),
                'totalAmount' => $this->transformMoneyWithCurrency($shippingGross, $currencyCode),
                'vatAmount' => $this->transformMoneyWithCurrency($vatAmount, $currencyCode),
                'vatRate' => sprintf("%01.2f", $vatRate),
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
        $itemPriceGross = $orderItem->getItemRetailPrice(true);
        $itemDiscountGross = $orderItem->getItemDiscount(true) * $orderItem->getQuantity();
        $itemTotal = $orderItem->getTotal(true);
        $taxAmount = $orderItem->getTotalTax();
        $taxRate = round(($taxAmount / $orderItem->getTotal(false)) * 100);


        $lineItem = [
            'name' => $orderItem->getName($locale),
            'type' => OrderLineType::TYPE_PHYSICAL,
            'quantity' => $orderItem->getQuantity(),
            'unitPrice' => $this->transformMoneyWithCurrency($itemPriceGross, $currencyCode),
            'discountAmount' => $this->transformMoneyWithCurrency($itemDiscountGross, $currencyCode),
            'totalAmount' => $this->transformMoneyWithCurrency($itemTotal, $currencyCode),
            'vatRate' => sprintf("%01.2f", $taxRate),
            'vatAmount' => $this->transformMoneyWithCurrency($taxAmount, $currencyCode),
        ];

        if ($orderItem instanceof CoreOrderItemInterface) {
            $lineItem['type'] = $orderItem->getDigitalProduct() === true ? OrderLineType::TYPE_DIGITAL : OrderLineType::TYPE_PHYSICAL;
        }

        if ($product instanceof ProductInterface) {
            $lineItem['sku'] = $product->getSku();

            if ($product->getImage() instanceof Asset\Image) {
                $lineItem['imageUrl'] = Tool::getHostUrl() . $product->getImage()->getFullPath();
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
