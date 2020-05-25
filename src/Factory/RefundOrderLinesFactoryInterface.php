<?php


namespace CoreShop\Payum\MollieBundle\Factory;


use CoreShop\Payum\MollieBundle\Request\Api\RefundOrderLines;

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
