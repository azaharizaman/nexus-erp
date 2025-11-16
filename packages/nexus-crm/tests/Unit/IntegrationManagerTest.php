<?php

declare(strict_types=1);

use Nexus\Crm\Core\IntegrationManager;
use Nexus\Crm\Contracts\IntegrationContract;
use Nexus\Crm\Models\CrmEntity;

class FakeIntegration implements IntegrationContract
{
    public function execute(CrmEntity $entity, array $config, array $context = []): void
    {
        // mark testing flag
        $data = $entity->data ?? [];
        $data['integration_executed'] = true;
        $entity->update(['data' => $data]);
    }

    public function compensate(CrmEntity $entity, array $config, array $context = []): void
    {
        $data = $entity->data ?? [];
        $data['integration_compensated'] = true;
        $entity->update(['data' => $data]);
    }
}

it('registers and runs custom integration', function () {
    $manager = new IntegrationManager();

    $manager->registerIntegration('fake', FakeIntegration::class);

    $entity = CrmEntity::create(['entity_type' => 'lead','definition_id' => 'd1','owner_id' => 'u1','data' => [],'status' => 'active']);

    $manager->execute('fake', $entity, ['foo' => 'bar']);

    expect($entity->fresh()->data['integration_executed'] ?? false)->toBeTrue();
});
