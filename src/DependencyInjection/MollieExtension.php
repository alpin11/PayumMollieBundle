<?php


namespace CoreShop\Payum\MollieBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MollieExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        if (array_key_exists('CoreShopRefundBundle', $container->getParameter('kernel.bundles'))) {
            $loader->load('services/refund.yml');
        }

        if (array_key_exists('CoreShopOrderBundle', $container->getParameter('kernel.bundles'))) {
            $loader->load('services/order.yml');
        }
    }
}
