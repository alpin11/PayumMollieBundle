<?php


namespace CoreShop\Payum\MollieBundle\Factory;

use Alpin11\Payum\Mollie\Request\Api\RefundOrderLines;

interface RefundOrderLinesFactoryInterface
{
    /**
     * @param $model
     * @param array $orderLines
     *
     * @return RefundOrderLines
     */
    public function createNewWithModel($model, array $orderLines): RefundOrderLines;
}
