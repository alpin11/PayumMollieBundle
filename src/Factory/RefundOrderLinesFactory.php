<?php


namespace CoreShop\Payum\MollieBundle\Factory;

use CoreShop\Payum\MollieBundle\Request\Api\RefundOrderLines;

class RefundOrderLinesFactory implements RefundOrderLinesFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createNewWithModel($model, array $orderLines): RefundOrderLines
    {
        return new RefundOrderLines($model, $orderLines);
    }
}
