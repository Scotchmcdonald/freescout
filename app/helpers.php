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
        try {
            if (class_exists(\Modules\Billing\Models\BillingSetting::class)) {
                $setting = \Modules\Billing\Models\BillingSetting::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            }
        } catch (\Exception $e) {
            // Fail gracefully if table doesn't exist or other error
            return $default;
        }
        
        return $default;
    }
}
