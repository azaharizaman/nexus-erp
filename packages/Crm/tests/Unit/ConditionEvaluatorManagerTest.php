<?php

declare(strict_types=1);

use Nexus\Crm\Core\ConditionEvaluatorManager;
use Nexus\Crm\Contracts\ConditionEvaluatorContract;
use Nexus\Crm\Models\CrmEntity;

class AlwaysTrueEvaluator implements ConditionEvaluatorContract
{
    public function evaluate(array $condition, $entity, array $context = []): bool
    {
        return true;
    }
}

it('registers custom evaluator and uses it', function () {
    $manager = new ConditionEvaluatorManager();
    $manager->registerEvaluator('true', new AlwaysTrueEvaluator());

    $entity = CrmEntity::create(['entity_type' => 'lead','definition_id' => 'd1','owner_id' => 'u1','data' => [],'status' => 'active']);
    $cond = ['type' => 'true'];

    expect($manager->evaluate($cond, $entity))->toBeTrue();
});
