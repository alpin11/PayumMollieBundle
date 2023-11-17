<?php

namespace CoreShop\Payum\MollieBundle\Provider;

abstract class AbstractProvider
{
    public function __construct(protected int $decimalFactor, protected int $decimalPrecision)
    {
    }

    /**
     * @param int $amount
     * @param string $currencyCode
     *
     * @return string[]
     */
    protected function transformMoneyWithCurrency(int $amount, string $currencyCode): array
    {
        $value = (int)round((round($amount / $this->decimalFactor, $this->decimalPrecision) * 100), 0);

        return [
            'value' => sprintf("%01.2f", ($value / 100)),
            'currency' => $currencyCode
        ];
    }
}