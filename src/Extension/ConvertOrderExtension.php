<?php


namespace CoreShop\Payum\MollieBundle\Extension;

use Carbon\Carbon;
use CoreShop\Component\Core\Model\StoreInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Core\Model\ProductInterface;
use CoreShop\Payum\MollieBundle\Model\MollieCustomerInterface;

class ConvertOrderExtension extends AbstractConvertOrderExtension
{
    public function __construct(protected int|float $decimalFactor)
    {

    }

    /**
     * @inheritDoc
     */
    protected function doPostExecute(PaymentInterface $payment, OrderInterface $order, array $result = []): array
    {
        $customer = $order->getCustomer();

        $result['orderNumber'] = $order->getOrderNumber();
        $result['locale'] = $this->isLocaleValid(
            $this->getLocaleCode($order)
        );

        if ($customer instanceof MollieCustomerInterface && $customer->getDateOfBirth() instanceof Carbon) {
            $result['consumerDateOfBirth'] = $customer->getDateOfBirth()->format('Y-m-d');
        }

        $result['metadata'] = json_encode([
            'customerPimcoreId' => $customer->getId(),
            'orderPimcoreId' => $order->getId(),
            'paymentPimcoreId' => $payment->getId()
        ]);

        $result['shopperCountryMustMatchBillingCountry'] = false;

        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            if ($product instanceof ProductInterface && $product->getDigitalProduct() === true) {
                $result['shopperCountryMustMatchBillingCountry'] = true;
            }
        }


        return $result;
    }

    /**
     * Checking if the locale is supported by mollie, else it will set a default locale
     * https://docs.mollie.com/reference/v2/payments-api/create-payment
     *
     * @param string $locale
     * @return string
     */
    private function isLocaleValid(string $locale): string
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

    /**
     * @param OrderInterface $order
     *
     * @return string
     */
    private function getLocaleCode(OrderInterface $order): string
    {
        if (strpos($order->getLocaleCode(), '_') > -1) {
            return $order->getLocaleCode();
        }

        // locale has no country information --> add it via store
        /** @var StoreInterface $store */
        $store = $order->getStore();

        return sprintf('%s_%s', $order->getLocaleCode(), strtoupper($store->getBaseCountry()->getIsoCode()));

    }
}
