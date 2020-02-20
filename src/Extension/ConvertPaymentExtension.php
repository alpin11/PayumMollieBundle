<?php


namespace CoreShop\Payum\MollieBundle\Extension;


use ArrayObject;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Payum\MollieBundle\Model\MollieCustomerInterface;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Convert;

final class ConvertPaymentExtension implements ExtensionInterface
{
    /**
     * @inheritDoc
     */
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

        if (false === $payment instanceof PaymentInterface) {
            return;
        }

        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        $customer = $order->getCustomer();

        $data = [];

        $data['description'] = sprintf("Payment for order #%s", $order->getOrderNumber());
        $data['locale'] = $order->getLocaleCode();
        $data['metadata'] = json_encode([
            'customerId' => $customer->getId(),
            'orderId' => $order->getId(),
            'paymentId' => $order->getId()
        ]);

        $data['sequenceType'] = 'oneoff';

        if ($customer instanceof MollieCustomerInterface) {
            $data['customerId'] = $customer->getMollieCustomerId();
        }

        $result = ArrayObject::ensureArrayObject($request->getResult());
        $result = array_merge($data, $result);
        $request->setResult((array)$result);
    }

    /**
     * @inheritDoc
     */
    public function onPreExecute(Context $context)
    {

    }

    /**
     * @inheritDoc
     */
    public function onExecute(Context $context)
    {

    }

}
