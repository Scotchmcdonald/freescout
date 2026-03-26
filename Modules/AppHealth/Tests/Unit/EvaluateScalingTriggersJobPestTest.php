<?php

declare(strict_types=1);

use Modules\AppHealth\Contracts\TriggerEvaluatorContract;
use Modules\AppHealth\Jobs\EvaluateScalingTriggersJob;

uses(Tests\UnitTestCase::class);

test('job skips persistence when trigger evaluation is disabled', function (): void {
    config(['apphealth.trigger_evaluation_enabled' => false]);

    $evaluator = \Mockery::mock(TriggerEvaluatorContract::class);
    $evaluator->shouldNotReceive('persistDailyScorecard');

    $job = new EvaluateScalingTriggersJob;
    $job->handle($evaluator);

    expect(true)->toBeTrue();
});

test('job persists scorecard when trigger evaluation is enabled', function (): void {
    config(['apphealth.trigger_evaluation_enabled' => true]);

    $evaluator = \Mockery::mock(TriggerEvaluatorContract::class);
    $evaluator->shouldReceive('persistDailyScorecard')->once();

    $job = new EvaluateScalingTriggersJob;
    $job->handle($evaluator);

    expect(true)->toBeTrue();
});

test('job skips persistence when trigger evaluation is falsey integer', function (): void {
    config(['apphealth.trigger_evaluation_enabled' => 0]);

    $evaluator = \Mockery::mock(TriggerEvaluatorContract::class);
    $evaluator->shouldNotReceive('persistDailyScorecard');

    $job = new EvaluateScalingTriggersJob;
    $job->handle($evaluator);

    expect(true)->toBeTrue();
});
