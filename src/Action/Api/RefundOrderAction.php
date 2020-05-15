<?php


namespace CoreShop\Payum\MollieBundle\Action\Api;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Cancel;

class RefundOrderAction extends BaseApiAwareAction
{
    /**
     * @inheritDoc
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty([MollieDetails::ORDER_ID, MollieDetails::STATUS]);

        /** @var MollieApiClient $mollie */
        $mollie = $this->api->getMollieApi();

        if ($details[MollieDetails::STATUS] === OrderStatus::STATUS_AUTHORIZED) {
            $this->gateway->execute(new Cancel($details));
            return;
        }
        try {
            $order = $mollie->orders->get($details[MollieDetails::ORDER_ID]);

            $refund = $order->refundAll();
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
        return $request instanceof RefundOrder &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
