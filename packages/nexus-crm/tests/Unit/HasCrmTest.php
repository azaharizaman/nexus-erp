<?php

declare(strict_types=1);

use Nexus\Crm\Traits\HasCrm;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasCrm;

    protected $fillable = ['name', 'crm_data'];

    public array $crmConfiguration = [
        'first_name' => ['type' => 'string', 'required' => true],
        'last_name' => ['type' => 'string', 'required' => true],
        'email' => ['type' => 'string', 'required' => false],
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->crm_data = $attributes['crm_data'] ?? [];
    }

    // Override save() to do nothing for Level 1 testing
    public function save(array $options = [])
    {
        // Do nothing for Level 1
        return true;
    }
}

it('can add crm trait to a model', function () {
    $model = new TestModel(['name' => 'Test']);

    expect($model)->toBeInstanceOf(TestModel::class);
    expect(method_exists($model, 'crm'))->toBeTrue();
});

it('can add a contact to a model', function () {
    $model = new TestModel(['name' => 'Test']);

    $contactData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ];

    $model->addContact($contactData);

    $contacts = $model->getContacts();
    expect($contacts)->toHaveCount(1);

    $contact = $contacts->first();
    expect($contact['first_name'])->toBe('John');
    expect($contact['last_name'])->toBe('Doe');
    expect($contact['email'])->toBe('john@example.com');
    expect($contact)->toHaveKey('id');
    expect($contact)->toHaveKey('created_at');
});

it('validates required fields when adding contact', function () {
    $model = new TestModel(['name' => 'Test']);

    $contactData = [
        'first_name' => 'John',
        // Missing last_name which is required
        'email' => 'john@example.com',
    ];

    expect(fn() => $model->addContact($contactData))
        ->toThrow(\InvalidArgumentException::class, "Field 'last_name' is required");
});

it('can update a contact', function () {
    $model = new TestModel(['name' => 'Test']);

    $contactData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ];

    $model->addContact($contactData);
    $contact = $model->getContacts()->first();
    $contactId = $contact['id'];

    $model->updateContact($contactId, [
        'email' => 'john.doe@example.com',
    ]);

    $updatedContact = $model->getContacts()->first();
    expect($updatedContact['email'])->toBe('john.doe@example.com');
    expect($updatedContact['first_name'])->toBe('John'); // Unchanged
});

it('can delete a contact', function () {
    $model = new TestModel(['name' => 'Test']);

    $contactData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ];

    $model->addContact($contactData);
    expect($model->getContacts())->toHaveCount(1);

    $contact = $model->getContacts()->first();
    $contactId = $contact['id'];

    $model->deleteContact($contactId);
    expect($model->getContacts())->toHaveCount(0);
});

it('throws exception when updating non-existent contact', function () {
    $model = new TestModel(['name' => 'Test']);

    expect(fn() => $model->updateContact('non-existent-id', ['email' => 'test@example.com']))
        ->toThrow(\InvalidArgumentException::class, 'Contact with ID non-existent-id not found');
});

it('throws exception when deleting non-existent contact', function () {
    $model = new TestModel(['name' => 'Test']);

    expect(fn() => $model->deleteContact('non-existent-id'))
        ->toThrow(\InvalidArgumentException::class, 'Contact with ID non-existent-id not found');
});