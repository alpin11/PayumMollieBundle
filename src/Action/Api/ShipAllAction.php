<?php


namespace CoreShop\Payum\MollieBundle\Action\Api;


use CoreShop\Payum\MollieBundle\MollieDetails;
use CoreShop\Payum\MollieBundle\Request\Api\ShipAll;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class ShipAllAction extends BaseApiAwareAction
{
    public function execute($request)
    {
        /** @var $request ShipAll */
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty([MollieDetails::ORDER_ID, MollieDetails::STATUS]);

        if (in_array($details[MollieDetails::STATUS], [OrderStatus::STATUS_EXPIRED, OrderStatus::STATUS_CANCELED])) {
            return;
        }

        /** @var MollieApiClient $mollie */
        $mollie = $this->api->getMollieApi();


        try {
            $order = $mollie->orders->get($details[MollieDetails::ORDER_ID]);

            $order->shipAll();
        } catch (ApiException $e) {
            $details['transaction_refunding_order_failed'] = true;
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    public function supports($request)
    {
        return $request instanceof ShipAll &&
            $request->getModel() instanceof \ArrayAccess;
    }
}