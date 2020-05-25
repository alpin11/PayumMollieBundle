<?php


namespace CoreShop\Payum\MollieBundle\Factory;

use CoreShop\Payum\MollieBundle\Request\Api\CreateShipment;

class CreateShipmentFactory implements CreateShipmentFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createNewWithModel($model, array $orderLines, array $tracking = []): CreateShipment
    {
        return new CreateShipment($model, $orderLines, $tracking);
    }
}
