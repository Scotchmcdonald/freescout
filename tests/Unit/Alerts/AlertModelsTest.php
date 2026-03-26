<?php

declare(strict_types=1);

namespace Tests\Unit\Alerts;

use Modules\Alerts\Models\AlertType;
use Tests\PureUnitTestCase;

// ─────────────────────────────────────────────────────────────────────────────
// AlertType: constant / category correctness (pure, no DB)
// ─────────────────────────────────────────────────────────────────────────────

final class AlertTypeConstantsTest extends PureUnitTestCase
{
    public function test_severity_constants_are_distinct(): void
    {
        $severities = [
            AlertType::SEVERITY_INFO,
            AlertType::SEVERITY_WARNING,
            AlertType::SEVERITY_ERROR,
            AlertType::SEVERITY_CRITICAL,
        ];

        $this->assertSame(count($severities), count(array_unique($severities)));
    }

    public function test_category_constants_match_categories_array(): void
    {
        $constants = [
            AlertType::CATEGORY_BILLING,
            AlertType::CATEGORY_SYNC,
            AlertType::CATEGORY_SECURITY,
            AlertType::CATEGORY_SYSTEM,
            AlertType::CATEGORY_MIGRATION,
            AlertType::CATEGORY_CONTRACT,
            AlertType::CATEGORY_SOFTWARE,
            AlertType::CATEGORY_ASSET,
            AlertType::CATEGORY_AI,
        ];

        foreach ($constants as $constant) {
            $this->assertContains($constant, AlertType::CATEGORIES, "Constant '{$constant}' missing from CATEGORIES");
        }
    }

    public function test_categories_array_has_no_duplicate_entries(): void
    {
        $this->assertSame(
            count(AlertType::CATEGORIES),
            count(array_unique(AlertType::CATEGORIES))
        );
    }

    public function test_all_category_constants_cover_categories_array(): void
    {
        // Every entry in CATEGORIES must have a corresponding constant
        $this->assertCount(9, AlertType::CATEGORIES);
    }
}
