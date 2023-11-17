<?php

namespace CoreShop\Payum\MollieBundle\Provider;

use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Order\Model\AdjustmentInterface;
use Mollie\Api\Types\OrderLineType;

class ShippingCostProvider extends AbstractProvider
{
    public function getShippingCostDetails(OrderInterface $order, PaymentInterface $payment): array
    {
        $shippingGross = $order->getShipping();
        $vatAmount = $order->getShippingTax();
        $vatRate = $order->getShippingTaxRate();
        $currencyCode = $payment->getCurrency()->getIsoCode();

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
}