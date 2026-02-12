<?php

declare(strict_types=1);

if (! function_exists('csp_nonce')) {
    /**
     * Get the CSP nonce for the current request.
     * Use this in inline script and style tags to comply with Content Security Policy.
     *
     * @return string
     */
    function csp_nonce(): string
    {
        $nonce = request()->attributes->get('csp_nonce', '');
        return is_string($nonce) ? $nonce : '';
    }
}

if (! function_exists('setting')) {
    /**
     * Get a billing setting value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        return $default;
    }
}

if (! function_exists('money')) {
    /**
     * Format a number as currency.
     *
     * @param mixed $amount
     * @param string $currency
     * @return string
     */
    function money($amount, $currency = '$')
    {
        return $currency . number_format(is_numeric($amount) ? (float) $amount : 0.0, 2);
    }
}

