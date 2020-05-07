<?php

namespace CoreShop\Payum\MollieBundle;

use Http\Message\MessageFactory;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Exceptions\IncompatiblePlatform;
use Mollie\Api\MollieApiClient;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class Api
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var array
     */
    private $method;


    /**
     * @var MollieApiClient
     */
    private $mollieApi;


    /**
     * @param string $apiKey
     * @param array $method
     */
    public function __construct(string $apiKey, array $method = [])
    {
        $this->apiKey = $apiKey;
        $this->method = $method;
    }

    /**
     * @return MollieApiClient
     *
     * @throws ApiException
     * @throws IncompatiblePlatform
     */
    public function getMollieApi()
    {
        if (is_null($this->mollieApi)) {
            $this->mollieApi = new MollieApiClient();
            $this->mollieApi->setApiKey($this->apiKey);
        }

        return $this->mollieApi;
    }

    /**
     * @return array
     */
    public function getMethod()
    {
        return $this->method;
    }
}
