<?php


namespace CoreShop\Payum\MollieBundle\Factory;

use Alpin11\Payum\Mollie\Request\Api\RefundOrder;

interface RefundOrderFactoryInterface
{
    public function createNewWithModel($model): RefundOrder;
}
