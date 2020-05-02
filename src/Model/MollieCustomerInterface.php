<?php


namespace CoreShop\Payum\MollieBundle\Model;

use Carbon\Carbon;

interface MollieCustomerInterface
{
    /**
     * @return int
     */
    public function getMollieCustomerId();

    /**
     * @return Carbon
     */
    public function getDateOfBirth();
}
