<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Workflow\Factories\ApprovalStrategyFactory;
use Nexus\Workflow\Models\ApproverGroup;
use Nexus\Workflow\Models\ApproverGroupMember;
use Nexus\Workflow\Models\UserTask;
use Nexus\Workflow\Services\ApproverGroupService;

uses(RefreshDatabase::class);

describe('ApproverGroupService - CRUD Operations', function () {
    beforeEach(function () {
        $this->service = new ApproverGroupService();
    });

    test('can create sequential approver group', function () {
        $group = $this->service->create([
            'name' => 'Sequential Approvers',
            'description' => 'Test sequential group',
            'strategy' => ApproverGroup::STRATEGY_SEQUENTIAL,
        ]);

        expect($group)->toBeInstanceOf(ApproverGroup::class);
        expect($group->name)->toBe('Sequential Approvers');
        expect($group->strategy)->toBe(ApproverGroup::STRATEGY_SEQUENTIAL);
    });

    test('can create quorum approver group', function () {
        $group = $this->service->create([
            'name' => 'Quorum Approvers',
            'strategy' => ApproverGroup::STRATEGY_QUORUM,
            'quorum_count' => 3,
        ]);

        expect($group->strategy)->toBe(ApproverGroup::STRATEGY_QUORUM);
        expect($group->quorum_count)->toBe(3);
    });

    test('can create weighted approver group', function () {
        $group = $this->service->create([
            'name' => 'Weighted Approvers',
            'strategy' => ApproverGroup::STRATEGY_WEIGHTED,
            'min_weight' => 100,
        ]);

        expect($group->strategy)->toBe(ApproverGroup::STRATEGY_WEIGHTED);
        expect($group->min_weight)->toBe(100);
    });

    test('throws exception when creating group without name', function () {
        $this->service->create([
            'strategy' => ApproverGroup::STRATEGY_PARALLEL,
        ]);
    })->throws(InvalidArgumentException::class, 'Group name is required');

    test('throws exception when creating group without strategy', function () {
        $this->service->create([
            'name' => 'Test Group',
        ]);
    })->throws(InvalidArgumentException::class, 'Approval strategy is required');

    test('throws exception for invalid strategy', function () {
        $this->service->create([
            'name' => 'Test Group',
            'strategy' => 'invalid_strategy',
        ]);
    })->throws(InvalidArgumentException::class, 'Invalid approval strategy');

    test('throws exception for quorum without quorum_count', function () {
        $this->service->create([
            'name' => 'Test Group',
            'strategy' => ApproverGroup::STRATEGY_QUORUM,
        ]);
    })->throws(InvalidArgumentException::class, 'Quorum count is required');

    test('throws exception for weighted without min_weight', function () {
        $this->service->create([
            'name' => 'Test Group',
            'strategy' => ApproverGroup::STRATEGY_WEIGHTED,
        ]);
    })->throws(InvalidArgumentException::class, 'Minimum weight is required');

    test('can update approver group', function () {
        $group = $this->service->create([
            'name' => 'Original Name',
            'strategy' => ApproverGroup::STRATEGY_PARALLEL,
        ]);

        $updated = $this->service->update((string) $group->id, [
            'name' => 'Updated Name',
            'description' => 'New description',
        ]);

        expect($updated->name)->toBe('Updated Name');
        expect($updated->description)->toBe('New description');
        expect($updated->strategy)->toBe(ApproverGroup::STRATEGY_PARALLEL);
    });

    test('can delete approver group', function () {
        $group = $this->service->create([
            'name' => 'To Delete',
            'strategy' => ApproverGroup::STRATEGY_ANY,
        ]);

        $result = $this->service->delete((string) $group->id);

        expect($result)->toBeTrue();
        expect($this->service->find((string) $group->id))->toBeNull();
    });

    test('can find approver group', function () {
        $group = $this->service->create([
            'name' => 'Find Me',
            'strategy' => ApproverGroup::STRATEGY_PARALLEL,
        ]);

        $found = $this->service->find((string) $group->id);

        expect($found)->toBeInstanceOf(ApproverGroup::class);
        expect($found->name)->toBe('Find Me');
    });

    test('can get all approver groups', function () {
        $this->service->create(['name' => 'Group 1', 'strategy' => ApproverGroup::STRATEGY_PARALLEL]);
        $this->service->create(['name' => 'Group 2', 'strategy' => ApproverGroup::STRATEGY_ANY]);

        $all = $this->service->all();

        expect($all)->toHaveCount(2);
    });

    test('can clone approver group', function () {
        $original = $this->service->create([
            'name' => 'Original Group',
            'strategy' => ApproverGroup::STRATEGY_QUORUM,
            'quorum_count' => 2,
        ]);

        $cloned = $this->service->clone((string) $original->id, 'Cloned Group');

        expect($cloned->name)->toBe('Cloned Group');
        expect($cloned->strategy)->toBe($original->strategy);
        expect($cloned->quorum_count)->toBe($original->quorum_count);
        expect($cloned->id)->not->toBe($original->id);
    });

    test('can get groups by strategy', function () {
        $this->service->create(['name' => 'Parallel 1', 'strategy' => ApproverGroup::STRATEGY_PARALLEL]);
        $this->service->create(['name' => 'Parallel 2', 'strategy' => ApproverGroup::STRATEGY_PARALLEL]);
        $this->service->create(['name' => 'Sequential 1', 'strategy' => ApproverGroup::STRATEGY_SEQUENTIAL]);

        $parallelGroups = $this->service->getByStrategy(ApproverGroup::STRATEGY_PARALLEL);

        expect($parallelGroups)->toHaveCount(2);
    });
});

describe('ApproverGroupService - Member Management', function () {
    beforeEach(function () {
        $this->service = new ApproverGroupService();
        $this->userId1 = (string) \Illuminate\Support\Str::uuid();
        $this->userId2 = (string) \Illuminate\Support\Str::uuid();
        $this->userId3 = (string) \Illuminate\Support\Str::uuid();
    });

    test('can add member to parallel group', function () {
        $group = $this->service->create([
            'name' => 'Parallel Group',
            'strategy' => ApproverGroup::STRATEGY_PARALLEL,
        ]);

        $member = $this->service->addMember((string) $group->id, $this->userId1);

        expect($member)->toBeInstanceOf(ApproverGroupMember::class);
        expect($member->user_id)->toBe($this->userId1);
    });

    test('can add member with sequence to sequential group', function () {
        $group = $this->service->create([
            'name' => 'Sequential Group',
            'strategy' => ApproverGroup::STRATEGY_SEQUENTIAL,
        ]);

        $member = $this->service->addMember((string) $group->id, $this->userId1, [
            'sequence' => 1,
        ]);

        expect($member->sequence)->toBe(1);
    });

    test('throws exception when adding to sequential group without sequence', function () {
        $group = $this->service->create([
            'name' => 'Sequential Group',
            'strategy' => ApproverGroup::STRATEGY_SEQUENTIAL,
        ]);

        $this->service->addMember((string) $group->id, $this->userId1);
    })->throws(InvalidArgumentException::class, 'Sequence is required');

    test('can add member with weight to weighted group', function () {
        $group = $this->service->create([
            'name' => 'Weighted Group',
            'strategy' => ApproverGroup::STRATEGY_WEIGHTED,
            'min_weight' => 100,
        ]);

        $member = $this->service->addMember((string) $group->id, $this->userId1, [
            'weight' => 50,
        ]);

        expect($member->weight)->toBe(50);
    });

    test('throws exception when adding to weighted group without weight', function () {
        $group = $this->service->create([
            'name' => 'Weighted Group',
            'strategy' => ApproverGroup::STRATEGY_WEIGHTED,
            'min_weight' => 100,
        ]);

        $this->service->addMember((string) $group->id, $this->userId1);
    })->throws(InvalidArgumentException::class, 'Weight is required');

    test('throws exception when adding duplicate member', function () {
        $group = $this->service->create([
            'name' => 'Test Group',
            'strategy' => ApproverGroup::STRATEGY_PARALLEL,
        ]);

        $this->service->addMember((string) $group->id, $this->userId1);
        $this->service->addMember((string) $group->id, $this->userId1);
    })->throws(InvalidArgumentException::class, 'already a member');

    test('can remove member from group', function () {
        $group = $this->service->create([
            'name' => 'Test Group',
            'strategy' => ApproverGroup::STRATEGY_PARALLEL,
        ]);

        $this->service->addMember((string) $group->id, $this->userId1);
        $result = $this->service->removeMember((string) $group->id, $this->userId1);

        expect($result)->toBeTrue();

        $members = $this->service->getMembers((string) $group->id);
        expect($members)->toHaveCount(0);
    });

    test('throws exception when removing non-existent member', function () {
        $group = $this->service->create([
            'name' => 'Test Group',
            'strategy' => ApproverGroup::STRATEGY_PARALLEL,
        ]);

        $this->service->removeMember((string) $group->id, $this->userId1);
    })->throws(InvalidArgumentException::class, 'not a member');

    test('can update member details', function () {
        $group = $this->service->create([
            'name' => 'Sequential Group',
            'strategy' => ApproverGroup::STRATEGY_SEQUENTIAL,
        ]);

        $this->service->addMember((string) $group->id, $this->userId1, ['sequence' => 1]);
        $updated = $this->service->updateMember((string) $group->id, $this->userId1, [
            'sequence' => 5,
        ]);

        expect($updated->sequence)->toBe(5);
    });

    test('can get ordered members', function () {
        $group = $this->service->create([
            'name' => 'Sequential Group',
            'strategy' => ApproverGroup::STRATEGY_SEQUENTIAL,
        ]);

        $this->service->addMember((string) $group->id, $this->userId1, ['sequence' => 3]);
        $this->service->addMember((string) $group->id, $this->userId2, ['sequence' => 1]);
        $this->service->addMember((string) $group->id, $this->userId3, ['sequence' => 2]);

        $members = $this->service->getMembers((string) $group->id, true);

        expect($members->pluck('user_id')->toArray())->toBe([
            $this->userId2,
            $this->userId3,
            $this->userId1,
        ]);
    });

    test('deleting group deletes all members', function () {
        $group = $this->service->create([
            'name' => 'Test Group',
            'strategy' => ApproverGroup::STRATEGY_PARALLEL,
        ]);

        $this->service->addMember((string) $group->id, $this->userId1);
        $this->service->addMember((string) $group->id, $this->userId2);

        $this->service->delete((string) $group->id);

        $memberCount = ApproverGroupMember::where('approver_group_id', $group->id)->count();
        expect($memberCount)->toBe(0);
    });

    test('cloning group clones all members', function () {
        $group = $this->service->create([
            'name' => 'Original Group',
            'strategy' => ApproverGroup::STRATEGY_SEQUENTIAL,
        ]);

        $this->service->addMember((string) $group->id, $this->userId1, ['sequence' => 1]);
        $this->service->addMember((string) $group->id, $this->userId2, ['sequence' => 2]);

        $cloned = $this->service->clone((string) $group->id, 'Cloned Group');

        expect($cloned->members)->toHaveCount(2);
        expect($cloned->members->pluck('user_id')->toArray())->toBe([
            $this->userId1,
            $this->userId2,
        ]);
    });
});

describe('Approval Strategies - Sequential', function () {
    beforeEach(function () {
        $this->service = new ApproverGroupService();
        $this->strategy = ApprovalStrategyFactory::makeFromStrategy(ApproverGroup::STRATEGY_SEQUENTIAL);
        
        $this->userId1 = (string) \Illuminate\Support\Str::uuid();
        $this->userId2 = (string) \Illuminate\Support\Str::uuid();
        $this->userId3 = (string) \Illuminate\Support\Str::uuid();

        // Create group with 3 members in sequence
        $this->group = $this->service->create([
            'name' => 'Sequential Test',
            'strategy' => ApproverGroup::STRATEGY_SEQUENTIAL,
        ]);

        $this->service->addMember((string) $this->group->id, $this->userId1, ['sequence' => 1]);
        $this->service->addMember((string) $this->group->id, $this->userId2, ['sequence' => 2]);
        $this->service->addMember((string) $this->group->id, $this->userId3, ['sequence' => 3]);

        $this->group = $this->group->fresh(['members']);
    });

    test('returns false when no approvals', function () {
        $result = $this->strategy->evaluate(
            $this->group->members,
            collect(),
            []
        );

        expect($result)->toBeFalse();
    });

    test('returns false when only first approver approved', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, []);

        expect($result)->toBeFalse();
    });

    test('returns false when approval out of sequence', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId2, 'status' => 'completed'], // Second approved first
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, []);

        expect($result)->toBeFalse();
    });

    test('returns true when all approved in sequence', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
            (object) ['user_id' => $this->userId2, 'status' => 'completed'],
            (object) ['user_id' => $this->userId3, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, []);

        expect($result)->toBeTrue();
    });

    test('provides accurate progress information', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
        ]);

        $progress = $this->strategy->getProgress($this->group->members, $tasks, []);

        expect($progress['total_approvers'])->toBe(3);
        expect($progress['completed_approvals'])->toBe(1);
        expect($progress['pending_approvals'])->toBe(2);
        expect($progress['next_approver'])->toBe($this->userId2);
        expect($progress['is_complete'])->toBeFalse();
        expect($progress['progress_percentage'])->toBeGreaterThan(0);
    });
});

describe('Approval Strategies - Parallel', function () {
    beforeEach(function () {
        $this->service = new ApproverGroupService();
        $this->strategy = ApprovalStrategyFactory::makeFromStrategy(ApproverGroup::STRATEGY_PARALLEL);
        
        $this->userId1 = (string) \Illuminate\Support\Str::uuid();
        $this->userId2 = (string) \Illuminate\Support\Str::uuid();
        $this->userId3 = (string) \Illuminate\Support\Str::uuid();

        $this->group = $this->service->create([
            'name' => 'Parallel Test',
            'strategy' => ApproverGroup::STRATEGY_PARALLEL,
        ]);

        $this->service->addMember((string) $this->group->id, $this->userId1);
        $this->service->addMember((string) $this->group->id, $this->userId2);
        $this->service->addMember((string) $this->group->id, $this->userId3);

        $this->group = $this->group->fresh(['members']);
    });

    test('returns false when not all approved', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
            (object) ['user_id' => $this->userId2, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, []);

        expect($result)->toBeFalse();
    });

    test('returns true when all approved', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
            (object) ['user_id' => $this->userId2, 'status' => 'completed'],
            (object) ['user_id' => $this->userId3, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, []);

        expect($result)->toBeTrue();
    });

    test('order does not matter', function () {
        // Approve in different order
        $tasks = collect([
            (object) ['user_id' => $this->userId3, 'status' => 'completed'],
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
            (object) ['user_id' => $this->userId2, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, []);

        expect($result)->toBeTrue();
    });
});

describe('Approval Strategies - Quorum', function () {
    beforeEach(function () {
        $this->service = new ApproverGroupService();
        $this->strategy = ApprovalStrategyFactory::makeFromStrategy(ApproverGroup::STRATEGY_QUORUM);
        
        $this->userId1 = (string) \Illuminate\Support\Str::uuid();
        $this->userId2 = (string) \Illuminate\Support\Str::uuid();
        $this->userId3 = (string) \Illuminate\Support\Str::uuid();

        $this->group = $this->service->create([
            'name' => 'Quorum Test',
            'strategy' => ApproverGroup::STRATEGY_QUORUM,
            'quorum_count' => 2,
        ]);

        $this->service->addMember((string) $this->group->id, $this->userId1);
        $this->service->addMember((string) $this->group->id, $this->userId2);
        $this->service->addMember((string) $this->group->id, $this->userId3);

        $this->group = $this->group->fresh(['members']);
    });

    test('returns false when below quorum', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, [
            'quorum_count' => 2,
        ]);

        expect($result)->toBeFalse();
    });

    test('returns true when quorum met', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
            (object) ['user_id' => $this->userId2, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, [
            'quorum_count' => 2,
        ]);

        expect($result)->toBeTrue();
    });

    test('returns true when more than quorum approved', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
            (object) ['user_id' => $this->userId2, 'status' => 'completed'],
            (object) ['user_id' => $this->userId3, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, [
            'quorum_count' => 2,
        ]);

        expect($result)->toBeTrue();
    });
});

describe('Approval Strategies - Any', function () {
    beforeEach(function () {
        $this->service = new ApproverGroupService();
        $this->strategy = ApprovalStrategyFactory::makeFromStrategy(ApproverGroup::STRATEGY_ANY);
        
        $this->userId1 = (string) \Illuminate\Support\Str::uuid();
        $this->userId2 = (string) \Illuminate\Support\Str::uuid();

        $this->group = $this->service->create([
            'name' => 'Any Test',
            'strategy' => ApproverGroup::STRATEGY_ANY,
        ]);

        $this->service->addMember((string) $this->group->id, $this->userId1);
        $this->service->addMember((string) $this->group->id, $this->userId2);

        $this->group = $this->group->fresh(['members']);
    });

    test('returns false when no approvals', function () {
        $result = $this->strategy->evaluate($this->group->members, collect(), []);

        expect($result)->toBeFalse();
    });

    test('returns true when any member approves', function () {
        $tasks = collect([
            (object) ['user_id' => $this->userId1, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, []);

        expect($result)->toBeTrue();
    });
});

describe('Approval Strategies - Weighted', function () {
    beforeEach(function () {
        $this->service = new ApproverGroupService();
        $this->strategy = ApprovalStrategyFactory::makeFromStrategy(ApproverGroup::STRATEGY_WEIGHTED);
        
        $this->ceoId = (string) \Illuminate\Support\Str::uuid();
        $this->cfoId = (string) \Illuminate\Support\Str::uuid();
        $this->managerId = (string) \Illuminate\Support\Str::uuid();

        $this->group = $this->service->create([
            'name' => 'Weighted Test',
            'strategy' => ApproverGroup::STRATEGY_WEIGHTED,
            'min_weight' => 75,
        ]);

        // CEO: 100, CFO: 50, Manager: 25
        $this->service->addMember((string) $this->group->id, $this->ceoId, ['weight' => 100]);
        $this->service->addMember((string) $this->group->id, $this->cfoId, ['weight' => 50]);
        $this->service->addMember((string) $this->group->id, $this->managerId, ['weight' => 25]);

        $this->group = $this->group->fresh(['members']);
    });

    test('returns false when weight below threshold', function () {
        $tasks = collect([
            (object) ['user_id' => $this->managerId, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, [
            'min_weight' => 75,
        ]);

        expect($result)->toBeFalse();
    });

    test('returns true when CEO alone approves', function () {
        $tasks = collect([
            (object) ['user_id' => $this->ceoId, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, [
            'min_weight' => 75,
        ]);

        expect($result)->toBeTrue();
    });

    test('returns true when CFO and Manager approve together', function () {
        $tasks = collect([
            (object) ['user_id' => $this->cfoId, 'status' => 'completed'],
            (object) ['user_id' => $this->managerId, 'status' => 'completed'],
        ]);

        $result = $this->strategy->evaluate($this->group->members, $tasks, [
            'min_weight' => 75,
        ]);

        expect($result)->toBeTrue();
    });

    test('provides accurate weight progress', function () {
        $tasks = collect([
            (object) ['user_id' => $this->cfoId, 'status' => 'completed'],
        ]);

        $progress = $this->strategy->getProgress($this->group->members, $tasks, [
            'min_weight' => 75,
        ]);

        expect($progress['completed_weight'])->toBe(50);
        expect($progress['remaining_weight'])->toBe(25);
        expect($progress['is_complete'])->toBeFalse();
    });
});

describe('ApprovalStrategyFactory', function () {
    test('can create all strategy types', function () {
        $strategies = [
            ApproverGroup::STRATEGY_SEQUENTIAL,
            ApproverGroup::STRATEGY_PARALLEL,
            ApproverGroup::STRATEGY_QUORUM,
            ApproverGroup::STRATEGY_ANY,
            ApproverGroup::STRATEGY_WEIGHTED,
        ];

        foreach ($strategies as $strategyName) {
            $strategy = ApprovalStrategyFactory::makeFromStrategy($strategyName);
            expect($strategy->getName())->toBe($strategyName);
        }
    });

    test('throws exception for unknown strategy', function () {
        ApprovalStrategyFactory::makeFromStrategy('unknown');
    })->throws(InvalidArgumentException::class);

    test('can get available strategies', function () {
        $strategies = ApprovalStrategyFactory::getAvailableStrategies();
        
        expect($strategies)->toHaveCount(5);
        expect($strategies)->toContain(ApproverGroup::STRATEGY_SEQUENTIAL);
        expect($strategies)->toContain(ApproverGroup::STRATEGY_PARALLEL);
    });
});
