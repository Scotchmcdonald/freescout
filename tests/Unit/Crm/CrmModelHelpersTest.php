<?php

declare(strict_types=1);

namespace Tests\Unit\Crm;

use Modules\Crm\Models\CustomField;
use Modules\Crm\Models\FieldDefinition;
use Tests\PureUnitTestCase;

/**
 * Stub to avoid DB dependency for pure attribute logic.
 */
final class StubFieldDefinition extends FieldDefinition
{
    protected static function booted(): void {}
}

/**
 * Stub to avoid DB dependency for CustomField attribute logic.
 */
final class StubCustomField extends CustomField
{
    protected static function booted(): void {}
}

/**
 * Pure-unit tests for CRM model helpers:
 * - FieldDefinition.getValidationRules()
 * - CustomField.getParsedValueAttribute()
 */
final class CrmModelHelpersTest extends PureUnitTestCase
{
    // ── FieldDefinition::getValidationRules ───────────────────────────────

    private function fieldDef(string $type, ?array $options = null): StubFieldDefinition
    {
        $fd = new StubFieldDefinition();
        $fd->type = $type;
        if ($options !== null) {
            $fd->setRawAttributes(array_merge($fd->getAttributes(), ['options' => json_encode($options)]));
        }

        return $fd;
    }

    public function test_validation_rules_number_type(): void
    {
        $rules = $this->fieldDef('number')->getValidationRules();
        $this->assertContains('numeric', $rules);
    }

    public function test_validation_rules_date_type(): void
    {
        $rules = $this->fieldDef('date')->getValidationRules();
        $this->assertContains('date', $rules);
    }

    public function test_validation_rules_boolean_type(): void
    {
        $rules = $this->fieldDef('boolean')->getValidationRules();
        $this->assertContains('boolean', $rules);
    }

    public function test_validation_rules_select_with_choices(): void
    {
        $rules = $this->fieldDef('select', ['choices' => ['active', 'inactive']])->getValidationRules();
        $this->assertCount(1, $rules);
        $this->assertStringStartsWith('in:', $rules[0]);
        $this->assertStringContainsString('active', $rules[0]);
        $this->assertStringContainsString('inactive', $rules[0]);
    }

    public function test_validation_rules_select_without_choices(): void
    {
        // select without options → no rule generated
        $rules = $this->fieldDef('select', [])->getValidationRules();
        $this->assertEmpty($rules);
    }

    public function test_validation_rules_default_type(): void
    {
        $rules = $this->fieldDef('text')->getValidationRules();
        $this->assertContains('string', $rules);
        $this->assertContains('nullable', $rules);
    }

    // ── CustomField::getParsedValueAttribute ──────────────────────────────

    private function customField(string $type, ?string $value): StubCustomField
    {
        $cf = new StubCustomField();
        $cf->setRawAttributes([
            'field_type' => $type,
            'field_value' => $value,
        ]);

        return $cf;
    }

    public function test_parsed_value_number(): void
    {
        $cf = $this->customField('number', '42.5');
        $this->assertSame(42.5, $cf->parsed_value);
    }

    public function test_parsed_value_boolean_truthy(): void
    {
        $cf = $this->customField('boolean', '1');
        $this->assertTrue($cf->parsed_value);
    }

    public function test_parsed_value_boolean_falsy(): void
    {
        $cf = $this->customField('boolean', '');
        $this->assertFalse($cf->parsed_value);
    }

    public function test_parsed_value_date_creates_carbon(): void
    {
        $cf = $this->customField('date', '2026-01-15');
        $this->assertInstanceOf(\Carbon\Carbon::class, $cf->parsed_value);
        $this->assertSame(2026, $cf->parsed_value->year);
        $this->assertSame(1, $cf->parsed_value->month);
        $this->assertSame(15, $cf->parsed_value->day);
    }

    public function test_parsed_value_date_null_value(): void
    {
        $cf = $this->customField('date', null);
        $this->assertNull($cf->parsed_value);
    }

    public function test_parsed_value_json_decodes_array(): void
    {
        $cf = $this->customField('json', '{"key":"value"}');
        $this->assertIsArray($cf->parsed_value);
        $this->assertSame('value', $cf->parsed_value['key']);
    }

    public function test_parsed_value_json_null_returns_empty_array(): void
    {
        $cf = $this->customField('json', null);
        $this->assertIsArray($cf->parsed_value);
        $this->assertEmpty($cf->parsed_value);
    }

    public function test_parsed_value_default_returns_raw_string(): void
    {
        $cf = $this->customField('text', 'Hello world');
        $this->assertSame('Hello world', $cf->parsed_value);
    }
}
