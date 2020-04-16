<?php


namespace CoreShop\Payum\MollieBundle\Extension;

use Payum\Core\Bridge\Spl\ArrayObject;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Payum\MollieBundle\Model\MollieCustomerInterface;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Convert;

final class ConvertPaymentExtension implements ExtensionInterface
{

    /**
     * @var float
     */
    private $decimalFactor;

    public function __construct(float $decimalFactor)
    {
        $this->decimalFactor = $decimalFactor;
    }

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

        $data['amount'] = [
            'value' => number_format(($payment->getTotalAmount() / $this->decimalFactor), 2),
            'currency' => $payment->getCurrencyCode()
        ];
        $data['orderNumber'] = $order->getOrderNumber();
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
        $result = is_array($result) ? array_merge($data, $result) : $data;
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

    /**
     * @param OrderInterface $order
     *
     * @return array
     */
    private function getLines(OrderInterface $order)
    {
        $lines = [];

        foreach ($order->getItems() as $orderItem) {
            $lineItem = [];

            $lineItem['name'] = $orderItem->getName();
            $lineItem['quantity'] = $orderItem->getQuantity();
            $lineItem['unitPrice'] = $orderItem->getItemPrice(true) / $this->decimalFactor;
            $lineItem['discountAmount'] = $orderItem->getItemDiscount(true) / $this->decimalFactor;
            $lineItem['totalAmount'] = $orderItem->getTotal(true) / $this->decimalFactor;

            $lines[] = $lineItem;
        }

        return $lines;
    }
}
