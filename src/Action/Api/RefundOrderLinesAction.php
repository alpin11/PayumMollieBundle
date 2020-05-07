<?php


namespace CoreShop\Payum\MollieBundle\Action\Api;


use CoreShop\Payum\MollieBundle\MollieDetails;
use CoreShop\Payum\MollieBundle\Request\Api\RefundOrderLines;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Cancel;

class RefundOrderLinesAction extends BaseApiAwareAction
{

    /**
     * @inheritDoc
     */
    public function execute($request)
    {
        /** @var $request RefundOrderLines */
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty([MollieDetails::ORDER_ID, MollieDetails::IS_CANCELABLE]);

        /** @var MollieApiClient $mollie */
        $mollie = $this->api->getMollieApi();

        $linesToRefund = $request->getLines();

        if ($details[MollieDetails::STATUS] === OrderStatus::STATUS_AUTHORIZED) {
            $this->gateway->execute(new Cancel($details));
            return;
        }
        try {
            $order = $mollie->orders->get($details[MollieDetails::ORDER_ID]);

            $refund = $order->refund([
                'lines' => $linesToRefund
            ]);
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
        return $request instanceof RefundOrderLines &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
