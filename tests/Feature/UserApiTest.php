<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_current_user_returns_401_for_a_guest(): void
    {
        $this->getJson(route('api.user'))->assertUnauthorized();
    }

    public function test_user_can_update_only_the_name_on_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->patchJson(route('api.users.update', $user), [
                'name' => 'Updated Name',
                'email' => 'other-email@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);
    }

    public function test_updating_another_users_profile_returns_403(): void
    {
        [$user, $otherUser] = User::factory()->count(2)->create()->all();

        $this->actingAs($user, 'web')
            ->patchJson(route('api.users.update', $otherUser), ['name' => 'Changed Name'])
            ->assertForbidden();

        $this->assertDatabaseHas(User::class, [
            'id' => $otherUser->id,
            'name' => $otherUser->name,
        ]);
    }
}
