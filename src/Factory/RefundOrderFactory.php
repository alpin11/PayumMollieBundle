<?php


namespace CoreShop\Payum\MollieBundle\Factory;


use CoreShop\Payum\MollieBundle\Request\Api\RefundOrder;

class RefundOrderFactory implements RefundOrderFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createNewWithModel($model): RefundOrder
    {
        return new RefundOrder($model);
    }
}
