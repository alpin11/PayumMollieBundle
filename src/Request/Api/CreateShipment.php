<?php


namespace CoreShop\Payum\MollieBundle\Request\Api;

use Payum\Core\Request\Generic;

class CreateShipment extends Generic
{
    /**
     * @var array
     */
    private $lines;
    /**
     * @var array
     */
    private $tracking;

    public function __construct($model, array $lines, array $tracking)
    {
        $this->lines = $lines;
        $this->tracking = $tracking;

        parent::__construct($model);
    }

    /**
     * @return array
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * @return array
     */
    public function getTracking()
    {
        return $this->tracking;
    }
}
