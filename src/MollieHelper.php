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

        if (!is_array($linesInDetails) || count($linesInDetails) == 0) {
            return [];
        }

        foreach ($orderItems as $orderItem) {
            if (!isset($orderItem['orderItemId'])) {
                continue;
            }

            $orderItemId = $orderItem['orderItemId'];
            $quantity = $orderItem['quantity'] ?? 0;


            foreach ($linesInDetails as $mollieLine) {
                if (!isset($mollieLine['metadata'])) {
                    continue;
                }

                $metadata = json_decode($mollieLine['metadata'], true);
                $metadataOrderItemId = $metadata['orderItemPimcoreId'] ?? null;

                if ($metadataOrderItemId == $orderItemId) {
                    $mollieLines[] = [
                        'id' => $mollieLine['id'],
                        'quantity' => $quantity
                    ];
                }
            }
        }

        return $mollieLines;
    }
}
