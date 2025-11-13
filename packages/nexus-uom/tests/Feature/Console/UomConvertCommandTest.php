<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Feature\Console;

use Nexus\Uom\Console\Commands\UomConvertCommand;
use Nexus\Uom\Contracts\UnitConverter as UnitConverterContract;
use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Tests\TestCase;
use Brick\Math\BigDecimal;
use Symfony\Component\Console\Tester\CommandTester;

class UomConvertCommandTest extends TestCase
{
    public function testCommandOutputsConvertedValue(): void
    {
        $converter = new class implements UnitConverterContract {
            public array $calls = [];

            public function convert(BigDecimal|int|float|string $value, $from, $to, ?int $precision = null): BigDecimal
            {
                $this->calls[] = [$value, $from, $to, $precision];

                return BigDecimal::of('42.5');
            }

            public function convertToBase(BigDecimal|int|float|string $value, $unit, ?int $precision = null): BigDecimal
            {
                throw new \BadMethodCallException('Not used in this test.');
            }

            public function convertFromBase(BigDecimal|int|float|string $value, $unit, ?int $precision = null): BigDecimal
            {
                throw new \BadMethodCallException('Not used in this test.');
            }
        };

        $this->app->instance(UnitConverterContract::class, $converter);

    $command = $this->app->make(UomConvertCommand::class);
    $command->setLaravel($this->app);
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            'value' => '10',
            'from' => 'kg',
            'to' => 'g',
            '--precision' => '2',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame([['10', 'kg', 'g', 2]], $converter->calls);
    $this->assertStringContainsString('10 KG = 42.5 G', $commandTester->getDisplay());
    }

    public function testCommandHandlesConversionErrors(): void
    {
        $converter = new class implements UnitConverterContract {
            public function convert(BigDecimal|int|float|string $value, $from, $to, ?int $precision = null): BigDecimal
            {
                throw ConversionException::unitNotFound('MISSING');
            }

            public function convertToBase(BigDecimal|int|float|string $value, $unit, ?int $precision = null): BigDecimal
            {
                throw new \BadMethodCallException('Not used in this test.');
            }

            public function convertFromBase(BigDecimal|int|float|string $value, $unit, ?int $precision = null): BigDecimal
            {
                throw new \BadMethodCallException('Not used in this test.');
            }
        };

        $this->app->instance(UnitConverterContract::class, $converter);

    $command = $this->app->make(UomConvertCommand::class);
    $command->setLaravel($this->app);
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            'value' => '5',
            'from' => 'foo',
            'to' => 'bar',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString("Unit 'MISSING' could not be found", $commandTester->getDisplay());
    }
}
