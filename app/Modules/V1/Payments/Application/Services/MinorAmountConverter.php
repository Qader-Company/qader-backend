<?php

namespace App\Modules\V1\Payments\Application\Services;

use InvalidArgumentException;

class MinorAmountConverter
{
    public function toMinor(string $amount, string $currency): int
    {
        $currency = strtoupper($currency);
        $exponent = config("payments.currency_exponents.{$currency}");

        if (! is_int($exponent)) {
            throw new InvalidArgumentException(__('payments.unsupported_currency', ['currency' => $currency]));
        }

        if (! preg_match('/^\d+(?:\.\d+)?$/', $amount)) {
            throw new InvalidArgumentException(__('payments.invalid_amount'));
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = substr(str_pad($fraction, $exponent, '0'), 0, $exponent);

        return ((int) $whole * (10 ** $exponent))
            + (int) $fraction;
    }
}
