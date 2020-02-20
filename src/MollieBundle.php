<?php


namespace CoreShop\Payum\MollieBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class MollieBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    /**
     * @inheritDoc
     */
    protected function getComposerPackageName(): string
    {
        return 'alpin11/payum-mollie-bundle';
    }
}
