<?php


namespace CoreShop\Payum\MollieBundle\Extension;

use CoreShop\Component\Address\Model\AddressInterface;
use CoreShop\Component\Address\Model\StateInterface;
use CoreShop\Component\Address\Model\CountryInterface;
use CoreShop\Component\Customer\Model\CustomerInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;

final class ConvertOrderAddressesExtension extends AbstractConvertOrderExtension
{
    /**
     * @inheritDoc
     */
    protected function doPostExecute(PaymentInterface $payment, OrderInterface $order, $result = [])
    {
        $customer = $order->getCustomer();
        $shippingAddress = $order->getShippingAddress();
        $invoiceAddress = $order->getInvoiceAddress();

        if ($customer instanceof CustomerInterface) {
            if ($shippingAddress instanceof AddressInterface) {
                $result['shippingAddress'] = $this->transformAddress($customer, $shippingAddress, $order->getLocaleCode());
            }

            if ($invoiceAddress instanceof AddressInterface) {
                $result['billingAddress'] = $this->transformAddress($customer, $invoiceAddress, $order->getLocaleCode());
            }
        }

        return $result;
    }

    /**
     * @param CustomerInterface $customer
     * @param AddressInterface $address
     * @param null $locale
     *
     * @return array
     */
    private function transformAddress(CustomerInterface $customer, AddressInterface $address, $locale = null)
    {
        return [
            'organizationName' => $address->getCompany(),
            'title' => $address->getSalutation(),
            'givenName' => $address->getFirstname(),
            'familyName' => $address->getLastname(),
            'email' => $customer->getEmail(),
            'phone' => $address->getPhoneNumber(),
            'streetAndNumber' => $address->getStreet() . ' ' . $address->getNumber(),
            'postalCode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'region' => $address->getState() instanceof StateInterface ? $address->getState()->getName($locale) : null,
            'country' => $address->getCountry() instanceof CountryInterface ? $address->getCountry()->getIsoCode() : null,
        ];
    }
}