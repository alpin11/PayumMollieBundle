<?php

namespace CoreShop\Payum\MollieBundle\Action;

use CoreShop\Payum\MollieBundle\Action\Api\BaseApiAwareAction;
use CoreShop\Payum\MollieBundle\MollieDetails;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;

class NotifyAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param Notify $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if ($httpRequest->method == 'POST') {
            $details[MollieDetails::ORDER_ID] = $_POST['id'];

            $request->setModel($details);

            throw new HttpResponse('OK', 200);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
