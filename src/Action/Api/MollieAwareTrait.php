<?php


namespace CoreShop\Payum\MollieBundle\Action\Api;

use Mollie\Api\Exceptions\ApiException;

trait MollieAwareTrait
{
    /**
     * @param \ArrayAccess $details
     * @param ApiException $e
     * @param object $request
     */
    protected function populateDetailsWithError(\ArrayAccess $details, ApiException $e, $request)
    {
        $details['error_request'] = get_class($request);
        $details['error_file'] = $e->getFile();
        $details['error_line'] = $e->getLine();
        $details['error_code'] = (int)$e->getCode();
        $details['error_message'] = utf8_encode($e->getMessage());
    }
}
