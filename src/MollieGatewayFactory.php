<?php

namespace CoreShop\Payum\MollieBundle;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class MollieGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'mollie',
            'payum.factory_title' => 'Mollie',
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'apiKey' => '',
                'method' => []
            );

            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    $config['apiKey'],
                    $config['method']
                );
            };
        }
    }
}
