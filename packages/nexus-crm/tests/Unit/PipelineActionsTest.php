<?php

declare(strict_types=1);

use Nexus\Crm\Models\CrmPipeline;
use Nexus\Crm\Models\CrmStage;
use Nexus\Crm\Models\CrmDefinition;
use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmAssignment;
use Nexus\Crm\Actions\TransitionEntity;
use Nexus\Crm\Core\IntegrationManager;

class FakeIntegration2 implements \Nexus\Crm\Contracts\IntegrationContract
{
    public function execute(\Nexus\Crm\Models\CrmEntity $entity, array $config, array $context = []): void
    {
        $data = $entity->data ?? [];
        $data['fake_integration'] = true;
        $entity->update(['data' => $data]);
    }

    public function compensate(\Nexus\Crm\Models\CrmEntity $entity, array $config, array $context = []): void
    {
        // noop
    }
}

it('executes assign_users, update_field, integration and create_timer actions on transition', function () {
    // Setup pipeline and stages
    $pipeline = CrmPipeline::create(['name'=>'PA', 'entity_type'=>'lead', 'config'=>[], 'is_active'=>true]);

    $stage1 = CrmStage::create(['name'=>'S1','pipeline_id'=>$pipeline->id, 'order'=>1, 'config'=>[], 'is_active'=>true]);
    $stage2 = CrmStage::create(['name'=>'S2','pipeline_id'=>$pipeline->id, 'order'=>2, 'config'=>[
        'entry_actions' => [
            ['type' => 'assign_users', 'strategy' => 'manual', 'users' => ['u2' => 'assignee']],
            ['type' => 'update_field', 'field' => 'score', 'value' => 99],
            ['type' => 'integration', 'integration_type' => 'fake2', 'config' => []],
            ['type' => 'create_timer', 'name' => 'followup', 'delay_minutes' => 1],
        ],
    ], 'is_active' => true]);

    $definition = CrmDefinition::create(['name'=>'paction_def','type'=>'lead','schema'=>[],'pipeline_config'=>['initial_stage_id'=>$stage1->id],'is_active'=>true]);

    $entity = CrmEntity::create(['entity_type'=>'lead','definition_id'=>$definition->id,'owner_id'=>'u1','data'=>[],'status'=>'pending','current_stage_id'=>$stage1->id]);

    // Register fake integration
    app(IntegrationManager::class)->registerIntegration('fake2', FakeIntegration2::class);

    $result = app(TransitionEntity::class)->handle($entity, $stage2->id);

    expect($result)->toBeTrue();
    expect($entity->fresh()->data['score'])->toBe(99);
    expect(CrmAssignment::where('entity_id', $entity->id)->exists())->toBeTrue();
    expect($entity->fresh()->data['fake_integration'])->toBeTrue();
    expect($entity->timers()->where('name','followup')->exists())->toBeTrue();
});
