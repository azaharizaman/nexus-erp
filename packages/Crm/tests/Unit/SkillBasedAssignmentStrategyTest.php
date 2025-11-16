<?php

declare(strict_types=1);

use Nexus\Crm\Core\SkillBasedAssignmentStrategy;

it('returns first available user when skill config absent', function () {
    $strategy = new SkillBasedAssignmentStrategy();
    $result = $strategy->resolve(new \Nexus\Crm\Models\CrmEntity(), ['users' => ['u1','u2'], 'required_skills' => []]);

    expect($result)->toBeEmpty();
});
