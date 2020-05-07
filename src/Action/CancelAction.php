<?php

namespace CoreShop\Payum\MollieBundle\Action;

use CoreShop\Payum\MollieBundle\Action\Api\BaseApiAwareAction;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Cancel;

class CancelAction extends BaseApiAwareAction
{

    /**
     * @param mixed $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty([MollieDetails::ORDER_ID, MollieDetails::IS_CANCELABLE]);

        if ($details[MollieDetails::IS_CANCELABLE]) {

            /** @var MollieApiClient $mollie */
            $mollie = $this->api->getMollieApi();

            try {
                $order = $mollie->orders->cancel($details[MollieDetails::ORDER_ID]);

                $details[MollieDetails::STATUS] = $order->status;

                $request->setResult($details);
            } catch (ApiException $e) {
                $details['transaction_cancelling_failed'] = true;
                $this->populateDetailsWithError($details, $e, $request);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Cancel &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
