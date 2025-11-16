<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Feature\Console;

use Nexus\Uom\Console\Commands\UomSeedCommand;
use Nexus\Uom\Tests\TestCase;
use Illuminate\Console\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UomSeedCommandTest extends TestCase
{
    public function testCommandSeedsUsingConfiguredClass(): void
    {
        config(['uom.seeders.class' => FakeSeeder::class]);

        $command = $this->makeInterceptingCommand();
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Seeding database using FakeSeeder.', $tester->getDisplay());
        $this->assertCount(1, $command->calls);
        $this->assertSame('db:seed', $command->calls[0]['command']);
        $this->assertSame(FakeSeeder::class, $command->calls[0]['arguments']['--class']);
    }

    public function testCommandHonoursClassOption(): void
    {
        $command = $this->makeInterceptingCommand();
        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['--class' => SecondFakeSeeder::class, '--database' => 'sqlite', '--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $arguments = $command->calls[0]['arguments'];
        $this->assertSame(SecondFakeSeeder::class, $arguments['--class']);
        $this->assertSame('sqlite', $arguments['--database']);
        $this->assertTrue($arguments['--force']);
    }

    public function testCommandFailsWhenSeederCannotBeResolved(): void
    {
    config(['uom.seeders.class' => null]);
        $this->app->offsetUnset('uom.database.seeder');

        $command = $this->app->make(UomSeedCommand::class);
    $command->setLaravel($this->app);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Unable to resolve a seeder class', $tester->getDisplay());
    }

    public function testCommandFailsWhenSeederClassMissing(): void
    {
    config(['uom.seeders.class' => 'NonExistingSeeder']);

        $command = $this->app->make(UomSeedCommand::class);
    $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString("Seeder class 'NonExistingSeeder' does not exist", $tester->getDisplay());
    }

    private function makeInterceptingCommand(): InterceptingSeedCommand
    {
        $command = $this->app->make(InterceptingSeedCommand::class);
        $command->setLaravel($this->app);

        return $command;
    }
}

final class InterceptingSeedCommand extends UomSeedCommand
{
    public array $calls = [];

    protected function runCommand($command, array $arguments, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->calls[] = compact('command', 'arguments');

        return Command::SUCCESS;
    }
}

class FakeSeeder extends \Illuminate\Database\Seeder
{
    public function run(): void
    {
        // no-op for tests
    }
}

class SecondFakeSeeder extends \Illuminate\Database\Seeder
{
    public function run(): void
    {
        // no-op for tests
    }
}
