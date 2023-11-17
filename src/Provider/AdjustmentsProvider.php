<?php

namespace CoreShop\Payum\MollieBundle\Provider;

use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Order\Model\AdjustmentInterface;
use Mollie\Api\Types\OrderLineType;

class AdjustmentsProvider extends AbstractProvider
{
    public function getAdjustmentsDetails(OrderInterface $order, PaymentInterface $payment)
    {
        $lineItems = [];

        foreach ($order->getAdjustments() as $adjustment) {
            if ($adjustment->getNeutral()) {
                continue;
            }

            if (in_array($adjustment->getTypeIdentifier(), [AdjustmentInterface::SHIPPING, AdjustmentInterface::CART_PRICE_RULE])) {
                continue;
            }

            $transformedAdjustment = $this->transformAdjustmentToLineItem($adjustment, $payment->getCurrencyCode());#

            if (null !== $transformedAdjustment) {
                $lineItems[] = $transformedAdjustment;
            }
        }

        return $lineItems;
    }

    /**
     * @param AdjustmentInterface $adjustment
     * @param $currencyCode
     *
     * @return array
     */
    protected function transformAdjustmentToLineItem(AdjustmentInterface $adjustment, $currencyCode): ?array
    {
        $amountGross = $adjustment->getAmount();
        $amountNet = $adjustment->getAmount(false);

        if ($amountGross == 0 || $amountNet == 0) {
            return null;
        }

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
}