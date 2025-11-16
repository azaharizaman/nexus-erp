<?php

declare(strict_types=1);

use Nexus\Crm\Core\CrmManager;
use Nexus\Crm\Traits\HasCrm;
use Illuminate\Database\Eloquent\Model;

class CrmManagerTestModel extends Model
{
    use HasCrm;

    protected $fillable = ['name', 'crm_data'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->crm_data = $attributes['crm_data'] ?? [];
    }

    // No DB save for Level1
    public function save(array $options = [])
    {
        return true;
    }
}

it('can allow any action by default', function () {
    $model = new CrmManagerTestModel(['name' => 'Test']);

    $manager = new CrmManager($model);

    expect($manager->can('create_contact'))->toBeTrue();
});

it('returns history collection', function () {
    $model = new CrmManagerTestModel(['name' => 'Test']);
    $manager = new CrmManager($model);

    $history = $manager->history();

    expect($history)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($history)->toHaveCount(0);
});

it('can add history entries', function () {
    $model = new CrmManagerTestModel(['name' => 'Test']);
    $manager = new CrmManager($model);

    $manager->addHistoryEntry('created', ['foo' => 'bar']);

    $history = $manager->history();

    expect($history)->toHaveCount(1);
    $entry = $history->first();
    expect($entry['action'])->toBe('created');
    expect($entry['data'])->toBe(['foo' => 'bar']);
    expect($entry)->toHaveKey('id');
    expect($entry)->toHaveKey('timestamp');
});

it('returns configuration from the model', function () {
    $model = new CrmManagerTestModel(['name' => 'Test']);
    $model->crmConfiguration = [
        'first_name' => ['type' => 'string', 'required' => true],
    ];

    $manager = new CrmManager($model);

    $config = $manager->getConfiguration();
    expect($config)->toBeArray();
    expect($config)->toHaveKey('first_name');
});
