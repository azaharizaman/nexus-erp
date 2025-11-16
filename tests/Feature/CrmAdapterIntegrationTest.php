<?php

declare(strict_types=1);

use Nexus\Erp\Crm\Contracts\NexusCrmAdapterInterface;
use Nexus\Erp\Crm\Adapters\NexusCrmAdapter;
use Nexus\Erp\Providers\CrmServiceProvider;

it('has crm orchestration provider and adapter contract', function () {
    expect(class_exists(CrmServiceProvider::class))->toBeTrue();
    expect(interface_exists(NexusCrmAdapterInterface::class))->toBeTrue();
    expect(class_exists(NexusCrmAdapter::class))->toBeTrue();
});
