<?php


namespace CoreShop\Payum\MollieBundle\DependencyInjection;

use CoreShop\Component\Payment\PaymentTransitions;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('coreshop_mollie');
        $root = method_exists($treeBuilder, 'getRootNode') ? $treeBuilder->getRootNode() : $treeBuilder->root('coreshop_mollie');

        $root
            ->children()
                ->scalarNode('api_key')->cannotBeEmpty()->end()
                ->arrayNode('refund_listeners')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('coreshop_payment')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultFalse()->end()
                            ->end()
                        ->end()
                        ->arrayNode('coreshop_refund_bundle')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultFalse()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();


        return $treeBuilder;
    }
}
