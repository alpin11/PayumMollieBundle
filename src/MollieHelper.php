<?php


namespace CoreShop\Payum\MollieBundle;

use Payum\Core\Bridge\Spl\ArrayObject;

class MollieHelper
{
    /**
     * Takes an array of orderItem IDs and quantities and transforms it to
     * Mollie line item IDs and quantities
     *
     * @param ArrayObject $details
     * @param array $orderItems
     * @return array
     */
    public static function orderItemsToMollieLines(ArrayObject $details, array $orderItems)
    {
        $mollieLines = [];
        $linesInDetails = $details[MollieDetails::LINES];

        foreach ($orderItems as $orderItem) {
            $orderItemId = $orderItem['orderItemId'] ?? null;
            $quantity = $orderItem['quantity'] ?? 0;
            $isAdjustment = $orderItem['isAdjustment'];
            $amount = $orderItem['amount'] ?? null;


            foreach ($linesInDetails as $mollieLine) {
                if (is_object($mollieLine)) {
                    $mollieLine = (array)$mollieLine;
                }

                if (!isset($mollieLine['metadata'])) {
                    continue;
                }

                $metadata = json_decode($mollieLine['metadata'], true);

                if ($isAdjustment) {
                    $metadataTypeIdentifier = $metadata['typeIdentifier'] ?? null;

                    if ($metadataTypeIdentifier == $orderItemId) {
                        $mollieLines[] = [
                            'id' => $mollieLine['id'],
                            'amount' => $amount
                        ];
                        break;
                    }
                } else {
                    $metadataOrderItemId = $metadata['orderItemPimcoreId'] ?? null;

                    if ($metadataOrderItemId == $orderItemId) {
                        $mollieLines[] = [
                            'id' => $mollieLine['id'],
                            'quantity' => $quantity
                        ];
                        break;
                    }
                }
            }
        }

        return $mollieLines;
    }
}
