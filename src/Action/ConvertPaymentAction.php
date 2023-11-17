<?php

namespace CoreShop\Payum\MollieBundle\Action;

use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Payum\MollieBundle\Provider\DetailProvider;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Convert;

class ConvertPaymentAction implements ActionInterface
{
    use GatewayAwareTrait;

    public function __construct(protected DetailProvider $detailProvider)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var \CoreShop\Component\Core\Model\PaymentInterface $payment */
        $payment = $request->getSource();
        $details = $payment->getDetails();
        $order = $payment->getOrder();

        $details = [
            ...$details,
            ...$this->detailProvider->getDetails($order, $payment)
        ];

        $request->setResult($details);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() === 'array'
            ;
    }

}
