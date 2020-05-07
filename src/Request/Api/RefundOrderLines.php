<?php


namespace CoreShop\Payum\MollieBundle\Request\Api;

use Payum\Core\Request\Generic;

class RefundOrderLines extends Generic
{
    /**
     * @var array
     */
    private $lines;

    public function __construct($model, array $lines)
    {
        parent::__construct($model);
        $this->lines = $lines;
    }

    /**
     * @return array
     */
    public function getLines()
    {
        return $this->lines;
    }
}
