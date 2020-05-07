<?php


namespace CoreShop\Payum\MollieBundle\Extension;

use Carbon\Carbon;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Core\Model\ProductInterface;
use CoreShop\Payum\MollieBundle\Model\MollieCustomerInterface;

class ConvertOrderExtension extends AbstractConvertOrderExtension
{
    /**
     * @var int|float
     */
    private $decimalFactor;

    public function __construct($decimalFactor)
    {
        $this->decimalFactor = $decimalFactor;
    }

    /**
     * @inheritDoc
     */
    protected function doPostExecute(PaymentInterface $payment, OrderInterface $order, $result = [])
    {
        $customer = $order->getCustomer();

        $result['orderNumber'] = $order->getOrderNumber();
        $result['locale'] = $order->getLocaleCode();

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
}
