<?php

namespace CoreShop\Payum\MollieBundle\Provider;

use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentInterface;

class AmountProvider extends AbstractProvider
{
    public function getAmountDetails(OrderInterface $order, PaymentInterface $payment): array
    {
        return [
            'value' => sprintf("%01.2f", ($payment->getTotalAmount() / 100)),
            'currency' => $payment->getCurrencyCode()
        ];
    }
}