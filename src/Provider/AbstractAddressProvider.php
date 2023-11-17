<?php

namespace CoreShop\Payum\MollieBundle\Provider;

use CoreShop\Component\Address\Model\AddressInterface;
use CoreShop\Component\Address\Model\CountryInterface;
use CoreShop\Component\Address\Model\StateInterface;
use CoreShop\Component\Customer\Model\CustomerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;

abstract class AbstractAddressProvider extends AbstractProvider
{
    /**
     * @param CustomerInterface $customer
     * @param AddressInterface $address
     * @param null $locale
     *
     * @return array
     */
    protected function getAddressDetails(CustomerInterface $customer, AddressInterface $address, $locale = null): array
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