<?php


namespace CoreShop\Payum\MollieBundle\Factory;


use Alpin11\Payum\Mollie\Request\Api\RefundOrderLines;

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
