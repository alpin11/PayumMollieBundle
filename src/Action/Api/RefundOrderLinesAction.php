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

        $linesToRefund = $this->getLinesToRefund($details, $request->getLines());

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
     * @param ArrayObject $details
     * @param array $lines
     *
     * @return array
     */
    private function getLinesToRefund(ArrayObject $details, array $lines)
    {
        $linesToRefund = [];
        $linesInDetails = $details[MollieDetails::LINES];

        if (!is_array($linesInDetails) || count($linesInDetails) == 0) {
            return [];
        }

        foreach ($lines as $lineItem) {
            if (!isset($lineItem['orderItemId'])) {
                continue;
            }

            $orderItemId = $lineItem['orderItemId'];
            $quantity = $lineItem['quantity'] ?? 0;


            foreach ($linesInDetails as $lineInDetails) {
                if (!isset($lineInDetails['metadata'])) {
                    continue;
                }

                $metadata = json_decode($lineInDetails['metadata']);
                $metadataOrderItemId = $metadata['pimcoreOrderItemId'] ?? null;

                if ($metadataOrderItemId == $orderItemId) {
                    $linesToRefund[] = [
                        'id' => $lineInDetails['id'],
                        'quantity' => $quantity
                    ];
                }
            }
        }

        return $linesToRefund;
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
