<?php

declare(strict_types=1);

use Nexus\Crm\Core\PipelineEngine;
use Nexus\Crm\Models\CrmPipeline;
use Nexus\Crm\Models\CrmStage;
use Nexus\Crm\Models\CrmDefinition;
use Nexus\Crm\Models\CrmEntity;

it('returns available transitions from pipeline engine', function () {
    $pipeline = CrmPipeline::create(['name' => 'tst','entity_type'=>'lead','config'=>[],'is_active'=>true]);
    $stage1 = CrmStage::create(['pipeline_id' => $pipeline->id,'name'=>'a','order'=>1,'config'=>[],'is_active'=>true]);
    $stage2 = CrmStage::create(['pipeline_id' => $pipeline->id,'name'=>'b','order'=>2,'config'=>[],'is_active'=>true]);

    $definition = CrmDefinition::create(['name'=>'def1','type'=>'lead','schema'=>[],'is_active'=>true,'pipeline_config'=>['initial_stage_id'=>$stage1->id]]);
    $entity = CrmEntity::create(['entity_type'=>'lead','definition_id'=>$definition->id,'owner_id'=>'u1','data'=>[],'status'=>'active','current_stage_id'=>$stage1->id]);

    $engine = app(PipelineEngine::class);
    $available = $engine->getAvailableTransitions($entity);

    expect($available->pluck('id'))->toContain($stage2->id);
});
