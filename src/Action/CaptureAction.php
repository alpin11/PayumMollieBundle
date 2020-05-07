<?php

namespace CoreShop\Payum\MollieBundle\Action;

use CoreShop\Payum\MollieBundle\Action\Api\BaseApiAwareAction;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

class CaptureAction extends BaseApiAwareAction implements GenericTokenFactoryAwareInterface
{
    use GenericTokenFactoryAwareTrait;

    /**
     * @param Capture $request
     *
     * @throws ApiException
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        /** @var MollieApiClient $mollie */
        $mollie = $this->api->getMollieApi();
        $method = $this->api->getMethod();

        $notifyToken = $this->tokenFactory->createNotifyToken(
            $request->getToken()->getGatewayName(),
            $request->getToken()->getDetails()
        );

        $targetUrl = $request->getToken()->getAfterUrl();

        // set urls for redirect and notification
        $details['redirectUrl'] = $targetUrl;
        $details['webhookUrl'] = $notifyToken->getTargetUrl();

        // set payment methods
        $details['method'] = count($method) == 1 ? $method[0] : $method;

        $mollieOrder = $mollie->orders->create((array)$details);

        $redirect = $mollieOrder->getCheckoutUrl();

        throw new HttpRedirect($redirect);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
