<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Unit\Services;

use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Models\UomAlias;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Services\DefaultAliasResolver;
use Nexus\Uom\Tests\TestCase;

class DefaultAliasResolverTest extends TestCase
{
    public function testResolveRespectsCachingAndCaseInsensitivity(): void
    {
        $unit = UomUnit::factory()->create(['code' => 'KG']);
        UomAlias::factory()->for($unit, 'unit')->create(['alias' => 'kilogram', 'is_preferred' => true]);

        /** @var DefaultAliasResolver $resolver */
        $resolver = $this->app->make(DefaultAliasResolver::class);

        $resolved = $resolver->resolve(' kg ');
        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($unit));

        // Cached result should be returned without triggering additional queries.
        $second = $resolver->resolve('KG');
        $this->assertSame($resolved, $second);

        $aliases = $resolver->aliasesFor($unit);
        $this->assertSame(['KG', 'kilogram'], $aliases);
    }

    public function testResolveOrFailThrowsWhenMissing(): void
    {
        /** @var DefaultAliasResolver $resolver */
        $resolver = $this->app->make(DefaultAliasResolver::class);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage("Unit 'MISSING' could not be found for conversion.");

        $resolver->resolveOrFail('MISSING');
    }

    public function testAliasesForAcceptsStringIdentifiers(): void
    {
        $unit = UomUnit::factory()->create(['code' => 'L']);
        UomAlias::factory()->for($unit, 'unit')->create(['alias' => 'litre', 'is_preferred' => true]);

        /** @var DefaultAliasResolver $resolver */
        $resolver = $this->app->make(DefaultAliasResolver::class);

        $aliases = $resolver->aliasesFor('l', includeCode: false);

        $this->assertSame(['litre'], $aliases);
    }

    public function testResolveHandlesBlankAndNumericIdentifiers(): void
    {
        $unit = UomUnit::factory()->create(['code' => 'SEC']);

        /** @var DefaultAliasResolver $resolver */
        $resolver = $this->app->make(DefaultAliasResolver::class);

        $this->assertNull($resolver->resolve('   '));

        $resolved = $resolver->resolve((string) $unit->getKey());

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($unit));
    }
}
