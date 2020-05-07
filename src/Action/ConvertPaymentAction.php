<?php

namespace CoreShop\Payum\MollieBundle\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;

class ConvertPaymentAction implements ActionInterface
{
    use GatewayAwareTrait;

    /**
     * @var int
     */
    private $decimalFactor;

    public function __construct($decimalFactor)
    {
        $this->decimalFactor = $decimalFactor;
    }

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        unset($details['description']);

        $details['amount'] = [
            'value' => sprintf("%01.2f", ($payment->getTotalAmount() / $this->decimalFactor)),
            'currency' => $payment->getCurrencyCode()
        ];

        $request->setResult((array)$details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array';
    }
}
