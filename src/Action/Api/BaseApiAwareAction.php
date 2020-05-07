<?php

namespace CoreShop\Payum\MollieBundle\Action\Api;

use CoreShop\Payum\MollieBundle\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

abstract class BaseApiAwareAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;
    use MollieAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }
}
