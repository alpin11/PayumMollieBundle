<?php

namespace CoreShop\Payum\MollieBundle\Action;

use CoreShop\Payum\MollieBundle\Action\Api\BaseApiAwareAction;
use CoreShop\Payum\MollieBundle\MollieDetails;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\OrderLine;
use Mollie\Api\Types\OrderStatus;
use Mollie\Api\Types\PaymentStatus;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;

class StatusAction extends BaseApiAwareAction
{
    /**
     * @param GetStatusInterface $request
     *
     * @throws ApiException
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        /** @var MollieApiClient $mollie */
        $mollie = $this->api->getMollieApi();

        if ($details[MollieDetails::ORDER_ID]) {
            $mollieOrder = $mollie->orders->get($mollie);

            $status = $mollieOrder->status;
            $details[MollieDetails::STATUS] = $status;
            $details[MollieDetails::IS_CANCELABLE] = $mollieOrder->isCancelable;
            $details[MollieDetails::PROFILE_ID] = $mollieOrder->profileId;
            $details[MollieDetails::MODE] = $mollieOrder->mode;

            $lineItems = [];

            /**
             * @var $line OrderLine
             */
            foreach ($mollieOrder->lines as $line) {
                $lineItems[] = [
                    MollieDetails::LINE_ITEM_ID => $line->id,
                    MollieDetails::QUANTITY => $line->quantity
                ];
            }

            $details[MollieDetails::LINES] = $lineItems;
        }

        $status = $details[MollieDetails::STATUS];

        switch ($status) {
            case OrderStatus::STATUS_PENDING:
                $request->markPending();
                break;
            case OrderStatus::STATUS_PAID:
                $request->markCaptured();
                break;
            case OrderStatus::STATUS_AUTHORIZED:
                $request->markAuthorized();
                break;
            case OrderStatus::STATUS_CANCELED:
                $request->markCanceled();
                break;
            case OrderStatus::STATUS_EXPIRED:
                $request->markExpired();
                break;
            case OrderStatus::STATUS_CREATED:
            default:
                $request->markNew();
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
