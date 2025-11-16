<?php

declare(strict_types=1);

use Nexus\Crm\Core\ConditionEvaluatorManager;
use Nexus\Crm\Models\CrmEntity;

it('evaluates default simple conditions via manager', function () {
    $manager = new ConditionEvaluatorManager();
    $entity = CrmEntity::create(['entity_type'=>'lead','definition_id'=>'d','owner_id'=>'u','data' => ['score'=>75],'status'=>'active']);

    $cond = ['field' => 'data.score', 'operator' => 'greater_than', 'value' => 50];
    expect($manager->evaluate($cond, $entity))->toBeTrue();
});

it('evaluates default compound conditions via manager', function () {
    $manager = new ConditionEvaluatorManager();
    $entity = CrmEntity::create(['entity_type'=>'lead','definition_id'=>'d','owner_id'=>'u','data' => ['score'=>75,'status'=>'new'],'status'=>'active']);

    $cond = ['logic' => 'and', 'conditions' => [
        ['field' => 'data.score','operator' => 'greater_than','value'=>50],
        ['field' => 'data.status','operator' => 'equals','value'=>'new']
    ]];

    expect($manager->evaluate($cond, $entity))->toBeTrue();
});
