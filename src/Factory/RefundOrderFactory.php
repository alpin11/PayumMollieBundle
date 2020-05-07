<?php


namespace CoreShop\Payum\MollieBundle\Factory;


use Alpin11\Payum\Mollie\Request\Api\RefundOrder;

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
