<?php

declare(strict_types=1);

namespace Tests\Unit\CaseManager;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Modules\CaseManager\Models\CaseRecord;
use Tests\PureUnitTestCase;

/**
 * Isolated subclass: removes all casts so attributes are returned raw,
 * preventing the array/boolean Eloquent cast resolver from running.
 */
final class TestCaseRecord extends CaseRecord
{
    protected function casts(): array
    {
        return [];
    }
}

class CaseRecordHelperTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    /** @var list<string> */
    private array $checklistFields = [
        'greeted',
        'clear_problem_statement',
        'clear_ownership',
        'asked_clarifying_questions',
        'ran_diagnostics',
        'ran_problem_resolution_prompt',
        'assessed_for_quick_wins',
        'assessed_for_related_kb_articles',
        'assessed_article_relevance',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        $app->instance('config', new Repository([
            'casemanager' => [
                'checklist' => $this->checklistFields,
            ],
        ]));

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function makeRecord(array $attributes = []): TestCaseRecord
    {
        $record = new TestCaseRecord;
        foreach ($attributes as $key => $value) {
            $record->$key = $value;
        }

        return $record;
    }

    // ─── checklistStatus ─────────────────────────────────────────────

    public function test_checklist_status_returns_array_keyed_by_field_names(): void
    {
        $record = $this->makeRecord(['greeted' => true, 'clear_problem_statement' => false]);
        $status = $record->checklistStatus();

        $this->assertArrayHasKey('greeted', $status);
        $this->assertArrayHasKey('clear_problem_statement', $status);
    }

    public function test_checklist_status_reflects_true_attribute(): void
    {
        $record = $this->makeRecord(['greeted' => true]);
        $status = $record->checklistStatus();

        $this->assertTrue($status['greeted']);
    }

    public function test_checklist_status_reflects_false_attribute(): void
    {
        $record = $this->makeRecord(['greeted' => false]);
        $status = $record->checklistStatus();

        $this->assertFalse($status['greeted']);
    }

    public function test_checklist_status_unset_attribute_is_false(): void
    {
        $record = new TestCaseRecord;
        $status = $record->checklistStatus();

        $this->assertFalse($status['greeted']);
    }

    public function test_checklist_status_has_exactly_nine_entries(): void
    {
        $status = (new TestCaseRecord)->checklistStatus();
        $this->assertCount(9, $status);
    }

    // ─── checklistProgress ───────────────────────────────────────────

    public function test_checklist_progress_zero_when_nothing_checked(): void
    {
        $this->assertSame(0, (new TestCaseRecord)->checklistProgress());
    }

    public function test_checklist_progress_increments_per_true_field(): void
    {
        $record = $this->makeRecord([
            'greeted' => true,
            'clear_problem_statement' => true,
            'clear_ownership' => true,
        ]);
        $this->assertSame(3, $record->checklistProgress());
    }

    public function test_checklist_progress_nine_when_all_complete(): void
    {
        $attrs = array_fill_keys($this->checklistFields, true);
        $this->assertSame(9, $this->makeRecord($attrs)->checklistProgress());
    }

    // ─── isChecklistComplete ─────────────────────────────────────────

    public function test_is_checklist_complete_returns_false_when_partial(): void
    {
        $record = $this->makeRecord(['greeted' => true]);
        $this->assertFalse($record->isChecklistComplete());
    }

    public function test_is_checklist_complete_returns_true_when_all_done(): void
    {
        $attrs = array_fill_keys($this->checklistFields, true);
        $this->assertTrue($this->makeRecord($attrs)->isChecklistComplete());
    }

    // ─── isReadyForTech ──────────────────────────────────────────────

    public function test_is_ready_for_tech_returns_true_when_state_matches(): void
    {
        $record = $this->makeRecord(['state' => 'ready_for_tech']);
        $this->assertTrue($record->isReadyForTech());
    }

    public function test_is_ready_for_tech_returns_false_for_triaging_state(): void
    {
        $record = $this->makeRecord(['state' => 'triaging']);
        $this->assertFalse($record->isReadyForTech());
    }

    public function test_is_ready_for_tech_returns_false_for_new_state(): void
    {
        $record = $this->makeRecord(['state' => 'new']);
        $this->assertFalse($record->isReadyForTech());
    }

    // ─── decisionPathLabel ───────────────────────────────────────────

    public function test_decision_path_label_known_paths(): void
    {
        $cases = [
            'provide_kb_article' => 'KB Article Match',
            'reopen_and_link' => 'Recurring Issue — Link to Prior Ticket',
            'triage_and_clarify' => 'Needs Clarification',
            'immediate_remediation' => 'Immediate Remediation',
            'propose_ticket_split' => 'Multi-Issue — Ticket Split Proposed',
            'route_to_technician' => 'Routed to Technician',
        ];

        foreach ($cases as $path => $expectedLabel) {
            $record = $this->makeRecord(['decision_path' => $path]);
            $this->assertSame($expectedLabel, $record->decisionPathLabel(), "Failed for path: {$path}");
        }
    }

    public function test_decision_path_label_null_returns_not_determined(): void
    {
        $record = $this->makeRecord(['decision_path' => null]);
        $this->assertSame('Not determined', $record->decisionPathLabel());
    }

    public function test_decision_path_label_unknown_path_is_humanised(): void
    {
        $record = $this->makeRecord(['decision_path' => 'some_custom_path']);
        $this->assertSame('Some custom path', $record->decisionPathLabel());
    }

    // ─── isDecisionEngineProcessed ───────────────────────────────────

    public function test_is_decision_engine_processed_false_when_path_is_null(): void
    {
        $record = $this->makeRecord(['decision_path' => null]);
        $this->assertFalse($record->isDecisionEngineProcessed());
    }

    public function test_is_decision_engine_processed_true_when_path_is_set(): void
    {
        $record = $this->makeRecord(['decision_path' => 'provide_kb_article']);
        $this->assertTrue($record->isDecisionEngineProcessed());
    }

    // ─── hasHistoricalContext ────────────────────────────────────────

    public function test_has_historical_context_false_when_null(): void
    {
        $record = $this->makeRecord(['historical_context' => null]);
        $this->assertFalse($record->hasHistoricalContext());
    }

    public function test_has_historical_context_false_when_empty_array(): void
    {
        $record = $this->makeRecord(['historical_context' => []]);
        $this->assertFalse($record->hasHistoricalContext());
    }

    public function test_has_historical_context_true_when_populated(): void
    {
        $record = $this->makeRecord(['historical_context' => ['prior_tickets' => [42]]]);
        $this->assertTrue($record->hasHistoricalContext());
    }

    // ─── hasKbSearchResult ───────────────────────────────────────────

    public function test_has_kb_search_result_false_when_null(): void
    {
        $record = $this->makeRecord(['kb_search_result' => null]);
        $this->assertFalse($record->hasKbSearchResult());
    }

    public function test_has_kb_search_result_false_when_empty_array(): void
    {
        $record = $this->makeRecord(['kb_search_result' => []]);
        $this->assertFalse($record->hasKbSearchResult());
    }

    public function test_has_kb_search_result_true_when_populated(): void
    {
        $record = $this->makeRecord(['kb_search_result' => ['articles' => ['slug-1']]]);
        $this->assertTrue($record->hasKbSearchResult());
    }
}
