<?php

declare(strict_types=1);

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\UnitTestCase::class)
    ->in('Unit');
