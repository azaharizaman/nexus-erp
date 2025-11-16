<?php

declare(strict_types=1);

use Nexus\Crm\Core\ConditionEvaluator;
use Nexus\Crm\Models\CrmEntity;

it('evaluates simple equals', function () {
    $entity = CrmEntity::create([ 'entity_type' => 'lead','definition_id' => 'd1','owner_id' => 'u1','data' => ['score' => 10],'status' => 'active']);

    $evaluator = new ConditionEvaluator();
    expect($evaluator->evaluate(['type' => 'simple', 'field' => 'data.score', 'operator' => '==', 'value' => 10], $entity))->toBeTrue();
});

it('evaluates compound and/or', function () {
    $entity = CrmEntity::create([ 'entity_type' => 'lead','definition_id' => 'd1','owner_id' => 'u1','data' => ['status'=>'new','score' => 60],'status' => 'active']);

    $evaluator = new ConditionEvaluator();
    $cond = ['type'=>'compound','operator'=>'AND','conditions'=>[
        ['type'=>'simple','field'=>'data.score','operator'=>'>','value'=>50],
        ['type'=>'simple','field'=>'data.status','operator'=>'==','value'=>'new']
    ]];

    expect($evaluator->evaluate($cond, $entity))->toBeTrue();
});

it('supports in operator', function () {
    $entity = CrmEntity::create([ 'entity_type' => 'lead','definition_id' => 'd1','owner_id' => 'u1','data' => ['region' => 'emea'],'status' => 'active']);
    $evaluator = new ConditionEvaluator();
    $cond = ['type' => 'simple','field'=>'data.region','operator'=>'in','value'=>['emea','apac']];
    expect($evaluator->evaluate($cond, $entity))->toBeTrue();
});
