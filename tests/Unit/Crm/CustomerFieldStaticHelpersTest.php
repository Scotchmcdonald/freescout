<?php

declare(strict_types=1);

namespace Tests\Unit\Crm;

use Modules\Crm\Entities\CustomerField;
use Tests\PureUnitTestCase;

/**
 * Stub that prevents DB-touching observers / Rememberable cache driver
 * from interfering with pure-logic tests.
 */
if (! class_exists(StubCustomerField::class)) {
    final class StubCustomerField extends CustomerField
    {
        protected static function booted(): void {}

        /** Disable Rememberable so no cache driver is needed. */
        public function remember(int $minutes, ?string $cacheTag = null): static
        {
            return $this;
        }
    }
}

final class CustomerFieldStaticHelpersTest extends PureUnitTestCase
{
    // ─── decodeName ──────────────────────────────────────────────────────────

    public function test_decode_name_strips_cf_prefix(): void
    {
        $this->assertSame('42', CustomerField::decodeName('cf_42'));
    }

    public function test_decode_name_leaves_plain_string_unchanged(): void
    {
        $this->assertSame('42', CustomerField::decodeName('42'));
    }

    public function test_decode_name_only_strips_leading_prefix(): void
    {
        $this->assertSame('cf_nested', CustomerField::decodeName('cf_cf_nested'));
    }

    // ─── shortenLink ──────────────────────────────────────────────────────────

    public function test_shorten_link_leaves_short_urls_unchanged(): void
    {
        $short = 'https://example.com'; // 19 chars < 20
        $this->assertSame($short, CustomerField::shortenLink($short));
    }

    public function test_shorten_link_truncates_long_urls_with_ellipsis(): void
    {
        $long = 'https://very-long-url.example.com/path/to/resource';
        $result = CustomerField::shortenLink($long);
        $this->assertStringEndsWith('…', $result);
        $this->assertSame(CustomerField::SHORT_LINK_LENGTH + 1, mb_strlen($result)); // 20 + ellipsis char
    }

    public function test_shorten_link_exact_length_boundary_unchanged(): void
    {
        $exact = str_repeat('a', CustomerField::SHORT_LINK_LENGTH); // exactly 20
        $this->assertSame($exact, CustomerField::shortenLink($exact));
    }

    // ─── prepareMultiselectValues ─────────────────────────────────────────────

    public function test_prepare_multiselect_values_trims_whitespace(): void
    {
        $result = CustomerField::prepareMultiselectValues(['  foo  ', ' bar ']);
        $this->assertSame(['foo', 'bar'], $result);
    }

    public function test_prepare_multiselect_values_removes_empty_strings(): void
    {
        $result = CustomerField::prepareMultiselectValues(['foo', '', '  ', 'bar']);
        $this->assertSame(['foo', 'bar'], $result);
    }

    public function test_prepare_multiselect_values_handles_null_elements(): void
    {
        $result = CustomerField::prepareMultiselectValues(['a', null, 'b']);
        $this->assertSame(['a', 'b'], $result);
    }

    public function test_prepare_multiselect_values_empty_array(): void
    {
        $this->assertSame([], CustomerField::prepareMultiselectValues([]));
    }

    // ─── explodeMultiselectValue ──────────────────────────────────────────────

    public function test_explode_multiselect_splits_by_comma(): void
    {
        $result = CustomerField::explodeMultiselectValue('php,laravel,testing');
        $this->assertSame(['php', 'laravel', 'testing'], $result);
    }

    public function test_explode_multiselect_trims_values(): void
    {
        $result = CustomerField::explodeMultiselectValue('php, laravel , testing');
        $this->assertSame(['php', 'laravel', 'testing'], $result);
    }

    public function test_explode_multiselect_single_value(): void
    {
        $result = CustomerField::explodeMultiselectValue('php');
        $this->assertSame(['php'], $result);
    }

    public function test_explode_multiselect_empty_string_returns_empty_array(): void
    {
        $result = CustomerField::explodeMultiselectValue('');
        $this->assertSame([], $result);
    }

    // ─── sanitizeValue – NUMBER type ─────────────────────────────────────────

    public function test_sanitize_value_number_casts_to_int(): void
    {
        $field = new StubCustomerField;
        $field->type = CustomerField::TYPE_NUMBER;

        $result = CustomerField::sanitizeValue('42', $field);
        $this->assertSame(42, $result);
    }

    public function test_sanitize_value_number_casts_float_string_to_int(): void
    {
        $field = new StubCustomerField;
        $field->type = CustomerField::TYPE_NUMBER;

        $result = CustomerField::sanitizeValue('3.7', $field);
        $this->assertSame(3, $result);
    }

    public function test_sanitize_value_number_empty_string_returned_as_is(): void
    {
        $field = new StubCustomerField;
        $field->type = CustomerField::TYPE_NUMBER;

        // Empty value → condition `if ($value && ...)` is false → returned unchanged
        $result = CustomerField::sanitizeValue('', $field);
        $this->assertSame('', $result);
    }

    // ─── sanitizeValue – DATE type ────────────────────────────────────────────

    public function test_sanitize_value_date_keeps_iso_format(): void
    {
        $field = new StubCustomerField;
        $field->type = CustomerField::TYPE_DATE;

        $result = CustomerField::sanitizeValue('2025-01-15', $field);
        $this->assertSame('2025-01-15', $result);
    }

    // ─── sanitizeValue – non-special types ────────────────────────────────────

    public function test_sanitize_value_single_line_type_passes_through_unchanged(): void
    {
        $field = new StubCustomerField;
        $field->type = CustomerField::TYPE_SINGLE_LINE;

        $result = CustomerField::sanitizeValue('hello world', $field);
        $this->assertSame('hello world', $result);
    }

    // ─── getNameEncoded ────────────────────────────────────────────────────────

    public function test_get_name_encoded_prepends_cf_prefix(): void
    {
        $field = new StubCustomerField;
        $field->id = 7;

        $this->assertSame('cf_7', $field->getNameEncoded());
    }

    // ─── type constant distinctness ────────────────────────────────────────────

    public function test_type_constants_are_distinct(): void
    {
        $types = [
            CustomerField::TYPE_DROPDOWN,
            CustomerField::TYPE_SINGLE_LINE,
            CustomerField::TYPE_MULTI_LINE,
            CustomerField::TYPE_NUMBER,
            CustomerField::TYPE_DATE,
            CustomerField::TYPE_LINK,
            CustomerField::TYPE_MULTISELECT,
        ];

        $this->assertSame(count($types), count(array_unique($types)));
    }

    // ─── getAsText ─────────────────────────────────────────────────────────────

    public function test_get_as_text_dropdown_returns_option_label(): void
    {
        $field = new StubCustomerField;
        $field->setRawAttributes([
            'type' => CustomerField::TYPE_DROPDOWN,
            'options' => json_encode(['key1' => 'Label One', 'key2' => 'Label Two']),
            'value' => 'key1',
        ]);

        $this->assertSame('Label One', $field->getAsText());
    }

    public function test_get_as_text_dropdown_falls_back_to_value_when_key_missing(): void
    {
        $field = new StubCustomerField;
        $field->setRawAttributes([
            'type' => CustomerField::TYPE_DROPDOWN,
            'options' => json_encode(['a' => 'Alpha']),
            'value' => 'unknown_key',
        ]);

        $this->assertSame('unknown_key', $field->getAsText());
    }

    public function test_get_as_text_multiselect_adds_spaces_after_delimiters(): void
    {
        $delimiterPair = CustomerField::MULTISELECT_DELIMITER.CustomerField::MULTISELECT_DELIMITER; // delimiter appears between values
        $field = new StubCustomerField;
        $delimiter = CustomerField::MULTISELECT_DELIMITER;
        $raw = "A{$delimiter}B{$delimiter}C";
        $field->setRawAttributes([
            'type' => CustomerField::TYPE_MULTISELECT,
            'value' => $raw,
        ]);

        $result = $field->getAsText();
        // Every delimiter should be followed by a space
        $this->assertStringContainsString($delimiter.' ', $result);
        $this->assertStringContainsString('A', $result);
        $this->assertStringContainsString('C', $result);
    }

    public function test_get_as_text_single_line_returns_raw_value(): void
    {
        $field = new StubCustomerField;
        $field->setRawAttributes([
            'type' => CustomerField::TYPE_SINGLE_LINE,
            'value' => 'plain text',
        ]);

        $this->assertSame('plain text', $field->getAsText());
    }

    public function test_get_as_text_returns_empty_string_when_value_null(): void
    {
        $field = new StubCustomerField;
        $field->setRawAttributes([
            'type' => CustomerField::TYPE_NUMBER,
            'value' => null,
        ]);

        $this->assertSame('', $field->getAsText());
    }

    // ─── getMultiselectValues ──────────────────────────────────────────────────

    public function test_get_multiselect_values_splits_on_delimiter(): void
    {
        $delimiter = CustomerField::MULTISELECT_DELIMITER;
        $field = new StubCustomerField;
        $field->setRawAttributes([
            'value' => "Alpha{$delimiter}Beta{$delimiter}Gamma",
        ]);

        $values = $field->getMultiselectValues();
        $this->assertContains('Alpha', $values);
        $this->assertContains('Gamma', $values);
    }

    public function test_get_multiselect_values_empty_string_returns_array(): void
    {
        $field = new StubCustomerField;
        $field->setRawAttributes(['value' => null]);

        $values = $field->getMultiselectValues();
        $this->assertIsArray($values);
    }
}
