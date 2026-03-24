<?php

declare(strict_types=1);

namespace Tests\Integration\Console\Commands;

use App\Console\Commands\Update;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\IntegrationTestCase;

/** @group console */
class ModuleUpdateAndUpdateCommandsTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function test_update_command_metadata_is_correct(): void
    {
        $command = new Update;

        $this->assertSame('freescout:update', $command->getName());
        $this->assertStringContainsString('update', strtolower($command->getDescription()));
        $this->assertTrue($command->getDefinition()->hasOption('force'));
    }

    public function test_handle_stops_when_confirmation_is_rejected(): void
    {
        $command = new TestableUpdateCommand;
        $command->confirmResult = false;

        $exitCode = $command->handle();

        $this->assertSame(1, $exitCode);
        $this->assertSame([], $command->calledCommands);
    }

    public function test_handle_returns_error_when_update_lock_is_already_held(): void
    {
        $command = new TestableUpdateCommand;
        $command->confirmResult = true;

        $lock = new UpdateCommandLock(false);

        Cache::shouldReceive('lock')
            ->once()
            ->with('freescout_update_process', 600)
            ->andReturn($lock);

        $exitCode = $command->handle();

        $this->assertSame(1, $exitCode);
        $this->assertContains('Another update process is currently running. Please wait.', $command->recordingOutput()->errors);
        $this->assertFalse($lock->released);
    }

    public function test_handle_runs_expected_command_chain_on_success(): void
    {
        $command = new TestableUpdateCommand;
        $command->confirmResult = true;

        $lock = new UpdateCommandLock(true);

        Cache::shouldReceive('lock')
            ->once()
            ->with('freescout_update_process', 600)
            ->andReturn($lock);

        $exitCode = $command->handle();

        $this->assertSame(0, $exitCode);
        $this->assertTrue($lock->released);
        $this->assertSame([
            ['migrate', ['--force' => true]],
            ['cache:clear', []],
            ['config:clear', []],
            ['route:clear', []],
            ['view:clear', []],
            ['optimize', []],
            ['freescout:after-app-update', []],
        ], $command->calledCommands);
        $this->assertContains('Starting FreeScout update...', $command->recordingOutput()->infos);
        $this->assertContains('Update completed successfully!', $command->recordingOutput()->infos);
    }

    public function test_handle_returns_error_when_inner_command_throws_and_releases_lock(): void
    {
        $command = new TestableUpdateCommand;
        $command->confirmResult = true;
        $command->throwOnCommand = 'migrate';

        $lock = new UpdateCommandLock(true);

        Cache::shouldReceive('lock')
            ->once()
            ->with('freescout_update_process', 600)
            ->andReturn($lock);

        $exitCode = $command->handle();

        $this->assertSame(1, $exitCode);
        $this->assertTrue($lock->released);
        $this->assertTrue(
            collect($command->recordingOutput()->errors)
                ->contains(fn (string $line): bool => str_contains($line, 'Error occurred during update: migrate failed'))
        );
    }
}

class TestableUpdateCommand extends Update
{
    public bool $confirmResult = true;

    public ?string $throwOnCommand = null;

    /** @var array<int, array{0: string, 1: array<string, mixed>}> */
    public array $calledCommands = [];

    private UpdateCommandRecordingOutputStyle $recordingOutput;

    public function __construct()
    {
        parent::__construct();

        $this->recordingOutput = new UpdateCommandRecordingOutputStyle;

        $property = new \ReflectionProperty(\Illuminate\Console\Command::class, 'output');
        $property->setAccessible(true);
        $property->setValue($this, $this->recordingOutput);
    }

    public function recordingOutput(): UpdateCommandRecordingOutputStyle
    {
        return $this->recordingOutput;
    }

    public function confirmToProceed($warning = 'Application In Production!', $callback = null)
    {
        return $this->confirmResult;
    }

    public function call($command, array $arguments = [])
    {
        $name = (string) $command;
        $this->calledCommands[] = [$name, $arguments];

        if ($this->throwOnCommand === $name) {
            throw new \RuntimeException($name.' failed');
        }

        return 0;
    }
}

class UpdateCommandLock
{
    public bool $released = false;

    public function __construct(private readonly bool $getResult) {}

    public function get(): bool
    {
        return $this->getResult;
    }

    public function release(): void
    {
        $this->released = true;
    }
}

class UpdateCommandRecordingOutputStyle extends OutputStyle
{
    /** @var array<int, string> */
    public array $infos = [];

    /** @var array<int, string> */
    public array $errors = [];

    public function __construct()
    {
        parent::__construct(new ArrayInput([]), new BufferedOutput);
    }

    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        $text = is_iterable($messages)
            ? implode(PHP_EOL, array_map(static fn (mixed $m): string => (string) $m, iterator_to_array($messages)))
            : (string) $messages;

        $plain = preg_replace('/<[^>]+>/', '', $text);
        $value = $plain ?? $text;

        if (str_contains($text, '<info>')) {
            $this->infos[] = $value;
        }

        if (str_contains($text, '<error>')) {
            $this->errors[] = $value;
        }

        parent::writeln($messages, $type);
    }
}
