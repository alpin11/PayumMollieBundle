<?php

namespace CoreShop\Payum\MollieBundle\Provider;

use CoreShop\Component\Core\Model\StoreInterface;
use CoreShop\Component\Order\Model\OrderInterface;

class LocaleProvider extends AbstractProvider
{
    public function getLocaleDetails(OrderInterface $order): string
    {
        return $this->getValidLocale(
            $this->getLocaleCodeFromOrder($order)
        );
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    private function getLocaleCodeFromOrder(OrderInterface $order): string
    {
        if (strpos($order->getLocaleCode(), '_') > -1) {
            return $order->getLocaleCode();
        }

        // locale has no country information --> add it via store
        /** @var StoreInterface $store */
        $store = $order->getStore();

        return sprintf('%s_%s', $order->getLocaleCode(), strtoupper($store->getBaseCountry()->getIsoCode()));
    }

    /**
     * Checking if the locale is supported by mollie, else it will set a default locale
     * https://docs.mollie.com/reference/v2/payments-api/create-payment
     *
     * @param string $locale
     * @return string
     */
    private function getValidLocale(string $locale): string
    {
        $validLocales = [
            'en_US',
            'nl_NL',
            'nl_BE',
            'fr_FR',
            'fr_BE',
            'de_DE',
            'de_AT',
            'de_CH',
            'es_ES',
            'ca_ES',
            'pt_PT',
            'it_IT',
            'nb_NO',
            'sv_SE',
            'fi_FI',
            'da_DK',
            'is_IS',
            'hu_HU',
            'pl_PL',
            'lv_LV',
            'lt_LT',
        ];

        return in_array($locale, $validLocales) ? $locale : 'en_US';
    }
}