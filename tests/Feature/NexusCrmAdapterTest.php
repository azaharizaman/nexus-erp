<?php

declare(strict_types=1);

namespace Tests\Feature;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Nexus\Erp\Providers\CrmServiceProvider;
use Nexus\Erp\Crm\Contracts\NexusCrmAdapterInterface;
use Nexus\Erp\Crm\Adapters\NexusCrmAdapter;
use Nexus\Crm\Core\Engine\CrmEngine;

/**
 * @group orchestrator
 */
class NexusCrmAdapterTest extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [CrmServiceProvider::class];
    }

    public function test_adapter_binding_and_calls()
    {
        $this->assertTrue($this->app->bound(NexusCrmAdapterInterface::class));

        $adapter = $this->app->make(NexusCrmAdapterInterface::class);

        $this->assertInstanceOf(NexusCrmAdapter::class, $adapter);

        // Provide a mock engine and bind it to container
        $mockEngine = $this->createMock(CrmEngine::class);

        $mockEngine->expects($this->any())->method('getDefinitionRegistry')->willReturn(null);

        $this->app->instance(CrmEngine::class, $mockEngine);

        // The call should be a no-op but should not throw
        $processed = $adapter->processTimersForTenant('tenant-1');
        $this->assertIsInt($processed);
    }
}
