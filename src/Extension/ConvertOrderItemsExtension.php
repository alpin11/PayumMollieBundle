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

final class ConvertOrderItemsExtension extends AbstractConvertOrderExtension
{
    /**
     * @var int
     */
    private $decimalFactor;
    /**
     * @var LinkGeneratorHelperInterface
     */
    private $linkGeneratorHelper;

    public function __construct(LinkGeneratorHelperInterface $linkGeneratorHelper, int $decimalFactor)
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
    private function transformPriceRuleItemToLineItem(ProposalCartPriceRuleItemInterface $priceRuleItem, $currencyCode, $localeCode)
    {
        $discountGross = $priceRuleItem->getDiscount(true);
        $discountNet = $priceRuleItem->getDiscount(false);
        $vatAmount = $discountGross - $discountNet;
        $vatRate = (int)round($vatAmount / $discountNet) * 100;

        return [
            'type' => OrderLineType::TYPE_DISCOUNT,
            'name' => empty($priceRuleItem->getVoucherCode()) ? $priceRuleItem->getCartPriceRule()->getLabel($localeCode) : sprintf("%s voucher code", $priceRuleItem->getVoucherCode()),
            'quantity' => 1,
            'unitPrice' => $this->transformMoneyWithCurrency($discountGross, $currencyCode),
            'totalAmount' => $this->transformMoneyWithCurrency($discountGross, $currencyCode),
            'vatAmount' => $this->transformMoneyWithCurrency($vatAmount, $currencyCode),
            'vatRate' => number_format($vatRate, 2, '.'),
            'metadata' => json_encode([
                'voucherCode' => $priceRuleItem->getVoucherCode(),
                'cartPriceRulePimcoreId' => $priceRuleItem->getCartPriceRule() instanceof CartPriceRuleInterface ? $priceRuleItem->getCartPriceRule()->getId() : null
            ])
        ];
    }

    /**
     * @param OrderItemInterface $orderItem
     * @param $currencyCode
     * @param null $locale
     *
     * @return array
     */
    private function transformOrderItemToLineItem(OrderItemInterface $orderItem, $currencyCode, $locale = null)
    {
        $product = $orderItem->getProduct();
        $itemPriceGross = $orderItem->getItemPrice(true);
        $itemDiscountGross = $orderItem->getItemDiscount(true);
        $itemTotal = $orderItem->getTotal(true);
        $taxRate = $this->getTaxRateFromOrderItem($orderItem);
        $taxAmount = $orderItem->getTotalTax();


        $lineItem = [
            'name' => $orderItem->getName($locale),
            'type' => OrderLineType::TYPE_PHYSICAL,
            'quantity' => $orderItem->getQuantity(),
            'unitPrice' => $this->transformMoneyWithCurrency($itemPriceGross, $currencyCode),
            'discountAmount' => $this->transformMoneyWithCurrency($itemDiscountGross, $currencyCode),
            'totalAmount' => $this->transformMoneyWithCurrency($itemTotal, $currencyCode),
            'vatRate' => number_format($taxRate, 2, '.'),
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

            $lineItem['productUrl'] = $this->linkGeneratorHelper->getUrl($product);
        }

        $lineItem['metadata'] = json_encode([
            'orderItemPimcoreId' => $orderItem->getId()
        ]);

        return $lineItem;
    }

    /**
     * @param OrderItemInterface $orderItem
     *
     * @return int
     */
    private function getTaxRateFromOrderItem(OrderItemInterface $orderItem)
    {
        $taxes = $orderItem->getTaxes();

        if ($taxes instanceof Fieldcollection) {
            if ($taxes->getCount() > 0) {
                /** @var TaxItemInterface $firstTaxItem */
                $firstTaxItem = $taxes->getItems()[0];

                return (int)$firstTaxItem->getRate();
            }
        }

        return 0;
    }

    /**
     * @param int $amount
     * @param string $currencyCode
     *
     * @return string[]
     */
    private function transformMoneyWithCurrency(int $amount, string $currencyCode)
    {
        return [
            'amount' => (string)number_format(($amount / $this->decimalFactor), 2, '.'),
            'currency' => $currencyCode
        ];
    }
}
