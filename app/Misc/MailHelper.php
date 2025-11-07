<?php

declare(strict_types=1);

namespace App\Misc;

use Illuminate\Support\Str;

class MailHelper
{
    /**
     * Generate artificial Message-ID.
     * Matches original FreeScout implementation.
     */
    public static function generateMessageId(string $email_address, string $raw_body = ''): string
    {
        $hash = Str::random(16);
        if ($raw_body) {
            $hash = md5($raw_body);
        }

        return 'fs-'.$hash.'@'.preg_replace('/.*@/', '', $email_address);
    }

    /**
     * Check if email headers indicate an auto-responder.
     * Matches original FreeScout implementation.
     */
    public static function isAutoResponder(?string $headersStr): bool
    {
        if (empty($headersStr)) {
            return false;
        }

        $autoresponderHeaders = [
            'x-autoreply' => '',
            'x-autorespond' => '',
            'x-autoresponder' => '',
            'auto-submitted' => '', // can be auto-replied, auto-generated, etc.
            'delivered-to' => ['autoresponder'],
            'precedence' => ['auto_reply', 'bulk', 'junk', 'list'],
            'x-precedence' => ['auto_reply', 'bulk', 'junk', 'list'],
        ];

        $headers = explode("\n", $headersStr);

        foreach ($autoresponderHeaders as $autoHeader => $autoHeaderValue) {
            foreach ($headers as $header) {
                $parts = explode(':', $header, 2);
                if (count($parts) != 2) {
                    continue;
                }

                $name = trim(strtolower($parts[0]));
                $value = trim($parts[1]);

                if (strtolower($name) == $autoHeader) {
                    if (! $autoHeaderValue) {
                        return true;
                    } elseif (is_array($autoHeaderValue)) {
                        foreach ($autoHeaderValue as $autoHeaderValueItem) {
                            if ($value == $autoHeaderValueItem) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get hash for message ID.
     */
    public static function getMessageIdHash(int $threadId): string
    {
        return md5($threadId.config('app.key'));
    }

    /**
     * Replace mail vars in the text.
     * Supports syntax: {%customer.fullName%} or {%customer.fullName,fallback=there%}
     * 
     * @param string $text Text containing mail variables to replace
     * @param array $data Array containing conversation, mailbox, customer, user objects
     * @param bool $escape Whether to escape HTML in replaced values
     * @param bool $remove_non_replaced Whether to remove unreplaced variables from output
     * @return string Text with variables replaced
     */
    public static function replaceMailVars(string $text, array $data = [], bool $escape = false, bool $remove_non_replaced = false): string
    {
        // Available variables to insert into email in UI.
        $vars = [];

        if (!empty($data['conversation'])) {
            $vars['{%subject%}'] = $data['conversation']->subject ?? '';
            $vars['{%conversation.number%}'] = $data['conversation']->number ?? '';
            $vars['{%customer.email%}'] = $data['conversation']->customer_email ?? '';
        }
        if (!empty($data['mailbox'])) {
            $vars['{%mailbox.email%}'] = $data['mailbox']->email ?? '';
            $vars['{%mailbox.name%}'] = $data['mailbox']->name ?? '';
            // To avoid recursion.
            if (isset($data['mailbox_from_name'])) {
                $vars['{%mailbox.fromName%}'] = $data['mailbox_from_name'];
            } else {
                $fromInfo = $data['mailbox']->getMailFrom(!empty($data['user']) ? $data['user'] : null);
                $vars['{%mailbox.fromName%}'] = is_array($fromInfo) ? ($fromInfo['name'] ?? '') : '';
            }
        }
        if (!empty($data['customer'])) {
            $vars['{%customer.fullName%}'] = $data['customer']->getFullName() ?? '';
            $vars['{%customer.firstName%}'] = $data['customer']->getFirstName() ?? '';
            $vars['{%customer.lastName%}'] = $data['customer']->last_name ?? '';
            $vars['{%customer.company%}'] = $data['customer']->company ?? '';
        }
        if (!empty($data['user'])) {
            $vars['{%user.fullName%}'] = $data['user']->getFullName() ?? '';
            $vars['{%user.firstName%}'] = $data['user']->getFirstName() ?? '';
            $vars['{%user.phone%}'] = $data['user']->phone ?? '';
            $vars['{%user.email%}'] = $data['user']->email ?? '';
            $vars['{%user.jobTitle%}'] = $data['user']->job_title ?? '';
            $vars['{%user.lastName%}'] = $data['user']->last_name ?? '';
            $vars['{%user.photoUrl%}'] = (is_object($data['user']) && method_exists($data['user'], 'getPhotoUrl')) ? $data['user']->getPhotoUrl() : '';
        }

        // Allow modules to add custom variables via Eventy filters
        if (function_exists('eventy')) {
            $vars = eventy('mail_vars.replace', $vars, $data);
        }

        /**
         * Retrieves all mail var codes from the text, including fallback values.
         * Pattern: {%varName%} or {%varName,fallback=value%}
         * 
         * @link https://regex101.com/r/icWukp/1
         */
        preg_match_all(
            '#\{%(?<var>[a-zA-Z.]+)(,fallback=(?<fallback>[^}]*))?%\}#',
            $text,
            $matches
        );

        // Add fallback values to the $vars array, if present.
        foreach ($matches['var'] as $i => $var) {
            $merge_code   = "{%{$var}%}";
            $full_match   = $matches[0][$i];
            $has_fallback = false !== strpos($full_match, ',fallback=');
            $fallback_val = $has_fallback ? ($matches['fallback'][$i] ?? null) : null;
            $merge_val    = isset($vars[$merge_code]) ? $vars[$merge_code] : $fallback_val;

            if (null !== $merge_val || true === $remove_non_replaced) {
                $vars[$full_match] = $merge_val ?? '';
                $vars[$merge_code] = $merge_val ?? '';
            }
        }

        // Allow modules to modify variables after fallback processing
        if (function_exists('eventy')) {
            $vars = eventy('mail_vars.replace_after_fallback', $vars, $data);
        }

        if ($escape) {
            foreach ($vars as $i => $var) {
                $vars[$i] = htmlspecialchars((string)($var ?? ''));
                $vars[$i] = nl2br($vars[$i]);
            }
        } else {
            foreach ($vars as $i => $var) {
                $vars[$i] = nl2br((string)($var ?? ''));
            }
        }

        $result = strtr($text, $vars);

        // Remove non-replaced placeholders.
        if ($remove_non_replaced) {
            $result = preg_replace('#\{%[^\.%\}]+\.[^%\}]+%\}#', '', $result);
            $result = $result !== null ? trim($result) : '';
        }

        return $result;
    }

    /**
     * Check if text has vars in it.
     */
    public static function hasVars(?string $text): bool
    {
        return (bool) preg_match('/({%|%})/', $text ?? '');
    }
}
