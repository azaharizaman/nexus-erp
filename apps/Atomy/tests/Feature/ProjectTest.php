<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Atomy\Models\Project;
use Nexus\Atomy\Models\User;

uses(RefreshDatabase::class);

test('user can create project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/projects', [
        'name' => 'Test Project',
        'description' => 'A test project',
        'start_date' => '2025-01-01',
        'budget' => 10000,
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['id', 'name', 'status']);

    $this->assertDatabaseHas('projects', [
        'name' => 'Test Project',
        'project_manager_id' => $user->id,
        'tenant_id' => $user->tenant_id,
    ]);
});

test('user can list projects', function () {
    $user = User::factory()->create();
    Project::factory()->create(['tenant_id' => $user->tenant_id]);

    $response = $this->actingAs($user)->getJson('/api/projects');

    $response->assertStatus(200)
             ->assertJsonCount(1);
});

test('user can view project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $user->tenant_id]);

    $response = $this->actingAs($user)->getJson("/api/projects/{$project->id}");

    $response->assertStatus(200)
             ->assertJson(['id' => $project->id]);
});

test('user can update project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $user->tenant_id]);

    $response = $this->actingAs($user)->putJson("/api/projects/{$project->id}", [
        'name' => 'Updated Project',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('projects', ['name' => 'Updated Project']);
});

test('user can delete project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $user->tenant_id]);

    $response = $this->actingAs($user)->deleteJson("/api/projects/{$project->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});