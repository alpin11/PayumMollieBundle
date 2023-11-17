<?php

namespace CoreShop\Payum\MollieBundle\Provider;

use CoreShop\Component\Address\Model\AddressInterface;
use CoreShop\Component\Core\Model\CustomerInterface;
use CoreShop\Component\Core\Model\OrderInterface;

class BillingAddressProvider extends AbstractAddressProvider
{
    public function getBillingAddressDetails(OrderInterface $order, CustomerInterface $customer, string $locale): ?array
    {
        $invoiceAddress = $order->getInvoiceAddress();

        if (!$invoiceAddress instanceof AddressInterface) {
            return null;
        }

        return $this->getAddressDetails($customer, $invoiceAddress, $locale);
    }
}