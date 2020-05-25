<?php


namespace CoreShop\Payum\MollieBundle\Factory;

use CoreShop\Payum\MollieBundle\Request\Api\CreateShipment;

interface CreateShipmentFactoryInterface
{
    /**
     * @param $model
     * @param array $orderLines
     * @param array $tracking
     *
     * @return CreateShipment
     */
    public function createNewWithModel($model, array $orderLines, array $tracking = []): CreateShipment;
}
