<?php

namespace Tests\Feature\Admin;

use App\Skill;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RestoreUsersTest extends TestCase
{
    use RefreshDatabase;


    /** @test */
    function restore_a_trashed_user()
    {
        $user = factory(User::class)->create();

        $skill = factory(Skill::class)->create([
            'name' => 'PHP'
        ]);

        $user->skills()->attach($skill);

        $user->delete();

        $this->from(route('users.trashed'))
            ->patch('usuarios/' . $user->id . '/restaurar');

        $this->assertDatabaseHas('users', [
            'deleted_at' => null,
            'id' => $user->id,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'deleted_at' => null,
            'user_id' => $user->id
        ]);

        $this->assertDatabaseHas('skill_user', [
            'deleted_at' => null,
            'user_id' => $user->id,
            'skill_id' => $skill->id
        ]);

    }

    /** @test */
    function cannot_restore_a_user_where_is_not_in_trash()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create([
            'deleted_at' => null,
        ]);

        $this->from(route('users.trashed'))
            ->patch('usuarios/' . $user->id . '/restaurar')
            ->assertStatus(404);
    }


}
