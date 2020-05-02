<?php


namespace CoreShop\Payum\MollieBundle\Extension;

use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Convert;

abstract class AbstractConvertOrderExtension implements ExtensionInterface
{
    public function onExecute(Context $context)
    {
        // nothing to do here
    }

    public function onPreExecute(Context $context)
    {
        // nothing to do here
    }

    public function onPostExecute(Context $context)
    {
        $action = $context->getAction();
        $previousActionClassName = get_class($action);

        if (false === stripos($previousActionClassName, 'ConvertPaymentAction')) {
            return;
        }

        /** @var Convert $request */
        $request = $context->getRequest();

        if (false === $request instanceof Convert) {
            return;
        }

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        if (!$payment instanceof PaymentInterface) {
            return;
        }

        $order = $payment->getOrder();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $result = ArrayObject::ensureArrayObject($request->getResult());

        $result = $this->doPostExecute($payment, $order, $result);

        $request->setResult($result);
    }

    /**
     * @param PaymentInterface $payment
     * @param OrderInterface $order
     * @param array $result
     *
     * @return array
     */
    abstract protected function doPostExecute(PaymentInterface $payment, OrderInterface $order, $result = []);
}
