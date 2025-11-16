<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Feature\Console;

use Nexus\Uom\Console\Commands\UomListUnitsCommand;
use Nexus\Uom\Models\UomAlias;
use Nexus\Uom\Models\UomType;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Tester\CommandTester;

class UomListUnitsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('uom_types', 'code')) {
            Schema::table('uom_types', static function (Blueprint $table): void {
                $table->string('code')->nullable();
            });
        }
    }

    public function testCommandListsUnitsWithAliases(): void
    {
        $type = UomType::factory()->create(['code' => 'length', 'name' => 'Length']);
        $unit = UomUnit::factory()->for($type, 'type')->base()->create([
            'code' => 'M',
            'name' => 'Metre',
            'conversion_factor' => '1',
            'offset' => '0',
        ]);
        UomAlias::factory()->for($unit, 'unit')->create(['alias' => 'metre', 'is_preferred' => true]);

    $command = $this->app->make(UomListUnitsCommand::class);
    $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['--aliases' => true]);

        $this->assertSame(0, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Aliases', $output);
        $this->assertStringContainsString('metre', $output);
        $this->assertStringContainsString('M', $output);
    }

    public function testCommandFiltersByTypeArgument(): void
    {
        $length = UomType::factory()->create(['code' => 'length']);
        $mass = UomType::factory()->create(['code' => 'mass']);

        UomUnit::factory()->for($length, 'type')->base()->create(['code' => 'M']);
        UomUnit::factory()->for($mass, 'type')->base()->create(['code' => 'G']);

    $command = $this->app->make(UomListUnitsCommand::class);
    $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['type' => 'length']);

        $this->assertSame(0, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('M', $output);
        $this->assertStringNotContainsString('G', $output);

        $secondExit = $tester->execute(['type' => (string) $length->getKey()]);
        $this->assertSame(0, $secondExit);
        $this->assertStringContainsString('M', $tester->getDisplay());
    }

    public function testCommandReturnsFailureWhenTypeNotFound(): void
    {
    $command = $this->app->make(UomListUnitsCommand::class);
    $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['type' => '9999']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString("Unable to resolve unit type '9999'", $tester->getDisplay());
    }

    public function testCommandWarnsWhenNoUnitsMatch(): void
    {
        UomType::factory()->create(['code' => 'void']);

        $command = $this->app->make(UomListUnitsCommand::class);
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['type' => 'void']);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No units found', $tester->getDisplay());
    }

    public function testCommandTreatsBlankTypeAsAllUnits(): void
    {
        UomType::factory()->create(['code' => 'len']);
        UomUnit::factory()->create(['code' => 'M']);

    $command = $this->app->make(UomListUnitsCommand::class);
    $command->setLaravel($this->app);

    $method = new \ReflectionMethod(UomListUnitsCommand::class, 'resolveType');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($command, '   '));
    }
}
