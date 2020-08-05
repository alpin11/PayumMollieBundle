<?php


namespace CoreShop\Payum\MollieBundle\Factory;


use CoreShop\Payum\MollieBundle\Request\Api\ShipAll;

interface ShipAllFactoryInterface
{
    /**
     * @param $model
     *
     * @return ShipAll
     */
    public function createNewWithModel($model): ShipAll;
}