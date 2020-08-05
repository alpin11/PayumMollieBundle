<?php


namespace CoreShop\Payum\MollieBundle\Factory;


use CoreShop\Payum\MollieBundle\Request\Api\ShipAll;

class ShipAllFactory implements ShipAllFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createNewWithModel($model): ShipAll
    {
        return new ShipAll($model);
    }
}