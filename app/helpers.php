<?php

if (! function_exists('csp_nonce')) {
    /**
     * Get the CSP nonce for the current request.
     * Use this in inline script and style tags to comply with Content Security Policy.
     *
     * @return string
     */
    function csp_nonce(): string
    {
        return request()->attributes->get('csp_nonce', '');
    }
}
