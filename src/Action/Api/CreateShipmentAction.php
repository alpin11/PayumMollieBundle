<?php


namespace CoreShop\Payum\MollieBundle\Action\Api;

use CoreShop\Payum\MollieBundle\MollieDetails;
use CoreShop\Payum\MollieBundle\MollieHelper;
use CoreShop\Payum\MollieBundle\Request\Api\CreateShipment;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class CreateShipmentAction extends BaseApiAwareAction
{

    /**
     * @inheritDoc
     */
    public function execute($request)
    {
        /** @var $request CreateShipment */
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty([MollieDetails::ORDER_ID, MollieDetails::STATUS]);

        if (in_array($details[MollieDetails::STATUS], [OrderStatus::STATUS_EXPIRED, OrderStatus::STATUS_CANCELED])) {
            return;
        }

        /** @var MollieApiClient $mollie */
        $mollie = $this->api->getMollieApi();
        $tracking = $request->getTracking();

        $params = [
            'lines' => MollieHelper::orderItemsToMollieLines($details, $request->getLines())
        ];

        if (!empty($tracking)) {
            $params['tracking'] = $tracking;
        }

        try {
            $order = $mollie->orders->get($details[MollieDetails::ORDER_ID]);

            $shipment = $order->createShipment($params);
        } catch (ApiException $e) {
            $details['transaction_refunding_order_failed'] = true;
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    /**
     * @inheritDoc
     */
    public function supports($request)
    {
        return $request instanceof CreateShipment &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
