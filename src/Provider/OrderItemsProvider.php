<?php

namespace CoreShop\Payum\MollieBundle\Provider;

use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\OrderItemInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Product\Model\ProductInterface;
use Mollie\Api\Types\OrderLineType;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Tool;

class OrderItemsProvider extends AbstractProvider
{

    public function getOrderItemsDetails(OrderInterface $order, PaymentInterface $payment): array
    {
        $lineItems = [];

        foreach ($order->getItems() as $item) {
            $lineItems[] = $this->transformOrderItemToLineItem($item, $payment->getCurrencyCode(), $order->getLocaleCode());
        }

        return $lineItems;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @param string $currencyCode
     * @param string|null $locale
     *
     * @return array
     */
    protected function transformOrderItemToLineItem(OrderItemInterface $orderItem, string $currencyCode, string $locale = null): array
    {
        $product = $orderItem->getProduct();
        $itemTotal = $orderItem->getTotal(true);
        $itemPrice = round($itemTotal / $orderItem->getQuantity());
        $taxAmount = $orderItem->getTotalTax();
        $taxRate = round(($taxAmount / $orderItem->getTotal(false)) * 100);

        $lineItem = [
            'name' => $orderItem->getName($locale),
            'quantity' => $orderItem->getQuantity(),
            'unitPrice' => $this->transformMoneyWithCurrency($itemPrice, $currencyCode),
            'totalAmount' => $this->transformMoneyWithCurrency($itemTotal, $currencyCode),
            'vatRate' => sprintf("%01.2f", $taxRate),
            'vatAmount' => $this->transformMoneyWithCurrency($taxAmount, $currencyCode),
        ];


        $lineItem['type'] = $orderItem->getDigitalProduct() === true ? OrderLineType::TYPE_DIGITAL : OrderLineType::TYPE_PHYSICAL;

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

}