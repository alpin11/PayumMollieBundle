<?php

namespace CoreShop\Payum\MollieBundle\Provider;

use CoreShop\Component\Address\Model\AddressInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Customer\Model\CustomerInterface;

class DetailProvider
{
    public function __construct(
        protected LocaleProvider          $localeProvider,
        protected ShippingAddressProvider $shippingAddressProvider,
        protected BillingAddressProvider  $billingAddressProvider,
        protected OrderItemsProvider      $orderItemsProvider,
        protected AdjustmentsProvider     $adjustmentsProvider,
        protected ShippingCostProvider    $shippingCostProvider,
        protected PriceRulesProvider      $priceRulesProvider,
        protected AmountProvider          $amountProvider
    )
    {

    }

    public function getDetails(OrderInterface $order, PaymentInterface $payment): array
    {
        $customer = $order->getCustomer();
        $shippingAddress = $order->getShippingAddress();
        $invoiceAddress = $order->getInvoiceAddress();

        $details = [];

        $details['amount'] = $this->amountProvider->getAmountDetails($order, $payment);
        $details['orderNumber'] = $order->getOrderNumber();
        $details['locale'] = $this->localeProvider->getLocaleDetails($order);


        if ($customer instanceof CustomerInterface) {
            if ($shippingAddress instanceof AddressInterface) {
                $details['shippingAddress'] = $this->shippingAddressProvider->getShippingAddressDetails($order, $customer, $order->getLocaleCode());
            }

            if ($invoiceAddress instanceof AddressInterface) {
                $details['billingAddress'] = $this->billingAddressProvider->getBillingAddressDetails($order, $customer, $order->getLocaleCode());
            }
        }

        $details['lines'] = [
            ...$this->orderItemsProvider->getOrderItemsDetails($order, $payment),
            ...$this->priceRulesProvider->getPriceRulesDetails($order, $payment),
            ...$this->shippingCostProvider->getShippingCostDetails($order, $payment),
            ...$this->adjustmentsProvider->getAdjustmentsDetails($order, $payment)
        ];

        $details['metadata'] = json_encode([
            'customerPimcoreId' => $customer->getId(),
            'orderPimcoreId' => $order->getId(),
            'paymentPimcoreId' => $payment->getId()
        ]);

        $details['shopperCountryMustMatchBillingCountry'] = false;

        return $details;
    }
}