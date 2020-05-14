<?php


namespace CoreShop\Payum\MollieBundle;

use Misd\PhoneNumberBundle\MisdPhoneNumberBundle;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;

class MollieBundle extends AbstractPimcoreBundle implements DependentBundleInterface
{
    use PackageVersionTrait;

    /**
     * @inheritDoc
     */
    protected function getComposerPackageName(): string
    {
        return 'alpin11/payum-mollie-bundle';
    }

    /**
     * @inheritDoc
     */
    public static function registerDependentBundles(BundleCollection $collection)
    {
        $collection->addBundle(new MisdPhoneNumberBundle());
    }
}
