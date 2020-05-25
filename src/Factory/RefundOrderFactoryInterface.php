<?php


namespace CoreShop\Payum\MollieBundle\Factory;



use CoreShop\Payum\MollieBundle\Request\Api\RefundOrder;

interface RefundOrderFactoryInterface
{
    public function createNewWithModel($model): RefundOrder;
}
