<?php

namespace Nexus\Uom\Console\Commands;

use Nexus\Uom\Contracts\UnitConverter;
use Nexus\Uom\Exceptions\ConversionException;
use Brick\Math\BigDecimal;
use Illuminate\Console\Command;

class UomConvertCommand extends Command
{
    protected $signature = 'uom:convert
        {value : The numeric quantity to convert}
        {from : Source unit code or identifier}
        {to : Target unit code or identifier}
        {--precision= : Optional precision override}';

    protected $description = 'Convert a quantity between units using the Laravel UOM package services.';

    public function handle(UnitConverter $converter): int
    {
        $value = $this->argument('value');
        $from = $this->argument('from');
        $to = $this->argument('to');
        $precision = $this->option('precision');

        $precisionValue = $precision !== null ? (int) $precision : null;

        try {
            $result = $converter->convert($value, $from, $to, $precisionValue);
        } catch (ConversionException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $resultValue = $result instanceof BigDecimal ? $result->__toString() : (string) $result;

        $this->line(sprintf('%s %s = %s %s', $value, strtoupper($from), $resultValue, strtoupper($to)));

        return self::SUCCESS;
    }
}
