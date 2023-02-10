<?php

namespace Tests\Feature\Admin;

use App\Profession;
use App\Skill;
use App\User;
use App\UserProfile;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateUsersTest extends TestCase
{
    use RefreshDatabase;

    protected $defaultData = [
        'first_name' => 'Pepe',
        'last_name' => 'Pérez',
        'email' => 'pepe@mail.es',
        'password' => '12345678',
        'profession_id' => '',
        'profession' => 'Estudiante',
        'bio' => 'Programador de Laravel y Vue.js',
        'twitter' => 'https://twitter.com/pepe',
        'role' => 'user',
    ];

    /** @test */
    function it_loads_the_edit_user_page()
    {
        $user = factory(User::class)->create();

        $this->get('usuarios/'.$user->id.'/editar')
            ->assertStatus(200)
            ->assertViewIs('users.edit')
            ->assertSee('Editar usuario')
            ->assertViewHas('user', function ($viewUser) use ($user) {
                return $viewUser->id === $user->id;
            });
    }

    /** @test */
    function it_updates_a_user()
    {
        $user = factory(User::class)->create();

        $oldProfession = factory(Profession::class)->create();
        $user->profile()->update([
            'profession_id' => $oldProfession->id,
        ]);

        $oldSkill1 = factory(Skill::class)->create();
        $oldSkill2 = factory(Skill::class)->create();
        $user->skills()->attach([$oldSkill1->id, $oldSkill2->id]);

        $newProfession = factory(Profession::class)->create();
        $newSkill1 = factory(Skill::class)->create();
        $newSkill2 = factory(Skill::class)->create();

        $this->put('usuarios/'.$user->id, $this->getValidData([
            'role' => 'admin',
            'profession_id' => $newProfession->id,
            'skills' => [$newSkill1->id, $newSkill2->id],
            'state' => 'inactive',
        ]))->assertRedirect('usuarios/' . $user->id);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Pepe',
            'last_name' => 'Pérez',
            'email' => 'pepe@mail.es',
            'role' => 'admin',
            'active' => false,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Programador de Laravel y Vue.js',
            'twitter' => 'https://twitter.com/pepe',
            'profession_id' => $newProfession->id,
        ]);

        $this->assertDatabaseCount('skill_user', 2);

        $this->assertDatabaseHas('skill_user', [
            'user_id' => $user->id,
            'skill_id' => $newSkill1->id,
        ]);

        $this->assertDatabaseHas('skill_user', [
            'user_id' => $user->id,
            'skill_id' => $newSkill2->id,
        ]);
    }

    /** @test */
    function the_first_name_is_required()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'first_name' => '',
            ]))->assertRedirect('usuarios/' . $user->id . '/editar')
            ->assertSessionHasErrors(['first_name']);

        $this->assertDatabaseMissing('users', ['email' => 'pepe@mail.es']);
    }

    /** @test */
    function the_last_name_is_required()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'last_name' => '',
            ]))->assertRedirect('usuarios/' . $user->id . '/editar')
            ->assertSessionHasErrors(['last_name']);

        $this->assertDatabaseMissing('users', ['email' => 'pepe@mail.es']);
    }

    /** @test */
    function the_email_is_required()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'email' => '',
            ]))->assertRedirect('usuarios/' . $user->id . '/editar')
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['first_name' => 'Pepe']);
    }

    /** @test */
    function the_email_must_be_valid()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'email' => 'correo-no-valido',
            ]))->assertRedirect('usuarios/' . $user->id . '/editar')
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['first_name' => 'Pepe']);
    }

    /** @test */
    function the_email_must_be_unique()
    {
        $this->withExceptionHandling();

        factory(User::class)->create([
            'email' => 'existing-email@mail.es'
        ]);

        $user = factory(User::class)->create([
            'email' => 'pepe@mail.es'
        ]);

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'email' => 'existing-email@mail.es',
            ]))->assertRedirect('usuarios/' . $user->id . '/editar')
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['first_name' => 'Pepe']);
    }

    /** @test */
    function the_user_email_can_stay_the_same()
    {
        $this->withExceptionHandling();
        
        $user = factory(User::class)->create([
            'email' => 'pepe@mail.es'
        ]);

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'first_name' => 'Pepe',
                'email' => 'pepe@mail.es',
            ]))->assertRedirect('usuarios/' . $user->id);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Pepe',
            'email' => 'pepe@mail.es',
        ]);
    }

    /** @test */
    function the_password_is_optional()
    {
        $oldPassword = 'CLAVE_VIEJA';

        $user = factory(User::class)->create([
            'password' => bcrypt($oldPassword),
        ]);

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'password' => '',
            ]))->assertRedirect('usuarios/' . $user->id);

        $this->assertCredentials([
            'first_name' => 'Pepe',
            'email' => 'pepe@mail.es',
            'password' => $oldPassword,
        ]);
    }

    /** @test */
    function it_detaches_all_skills_if_none_is_checked()
    {
        $user = factory(User::class)->create();

        $oldSkill1 = factory(Skill::class)->create();
        $oldSkill2 = factory(Skill::class)->create();
        $user->skills()->attach([$oldSkill1->id, $oldSkill2->id]);

        $this->put('usuarios/' . $user->id, $this->getValidData())
            ->assertRedirect('usuarios/' . $user->id);

        $this->assertDatabaseEmpty('skill_user');
    }

    /** @test */
    function the_state_is_required()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'state' => '',
            ]))->assertRedirect('usuarios/' . $user->id . '/editar')
            ->assertSessionHasErrors(['state']);

        $this->assertDatabaseMissing('users', ['first_name' => 'Pepe']);
    }

    /** @test  */
    function the_state_must_be_valid()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'state' => 'invalid-state',
            ]))->assertRedirect('usuarios/' . $user->id . '/editar')
            ->assertSessionHasErrors(['state']);

        $this->assertDatabaseMissing('users', ['first_name' => 'Pepe']);
    }

    /** @test  */
    function the_bio_is_required()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'bio' => null,
                'first_name' => 'Pepe',
            ]))->assertRedirect('usuarios/' . $user->id . '/editar')
            ->assertSessionHasErrors(['bio']);

        $this->assertDatabaseMissing('users', ['first_name' => 'Pepe']);
    }

    /** @test  */
    function the_twitter_is_nullable()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'twitter' => null,
                'first_name' => 'Pepe',
            ]))
            ->assertRedirect(route('user.show', $user));

        $this->assertDatabaseHas('users', ['first_name' => 'Pepe']);

        $this->assertDatabaseHas('user_profiles', ['twitter' => null]);
    }

    /** @test  */
    function the_twitter_must_be_present()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, [
                'first_name' => 'Pepe',
                'last_name' => 'Pérez',
                'email' => 'pepe@mail.es',
                'password' => '12345678',
                'profession_id' => '',
                'bio' => 'Programador de Laravel y Vue.js',
                'role' => 'user',
            ])
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors(['twitter']);

        $this->assertDatabaseMissing('users', ['first_name' => 'Pepe']);

        $this->assertDatabaseMissing('user_profiles', ['bio' => 'Programador de Laravel y Vue.js']);
    }

    /** @test  */
    function the_twitter_must_be_an_url()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from('usuarios/'.$user->id.'/editar')
            ->put('usuarios/'.$user->id, $this->getValidData([
                'twitter' => 'wadhoiuahwdoaodiu',
            ]))
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors(['twitter']);

        $this->assertDatabaseMissing('users', ['first_name' => 'Pepe']);

        $this->assertDatabaseMissing('user_profiles', ['twitter' => 'wadhoiuahwdoaodiu']);
    }

    /** @test */
    function the_profession_id_must_be_present()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from(route('users.edit', $user))
            ->put('usuarios/'. $user->id , [
                'first_name' => 'Pepe',
                'last_name' => 'Pérez',
                'email' => 'pepe@mail.es',
                'password' => '12345678',
                'bio' => 'Programador de Laravel y Vue.js',
                'twitter' => 'https://twitter.com/pepe',
                'role' => 'user',
                'state' => 'active',
            ])->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors(['profession_id']);

        $this->assertDatabaseMissing('users', [
            'first_name' => 'Pepe',
            'last_name' => 'Pérez',
        ]);

        $this->assertDatabaseMissing('user_profiles', [
            'bio' => 'Programador de Laravel y Vue.js',
            'twitter' => 'https://twitter.com/pepe',
        ]);
    }

    /** @test */
    function the_profession_id_cant_be_empty_if_profession_is_empty_and_vice_versa()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from(route('users.edit', $user))
            ->put('usuarios/'. $user->id , [
                'first_name' => 'Pepe',
                'last_name' => 'Pérez',
                'email' => 'pepe@mail.es',
                'password' => '12345678',
                'profession_id' => '',
                'profession' => null,
                'bio' => 'Programador de Laravel y Vue.js',
                'twitter' => 'https://twitter.com/pepe',
                'role' => 'user',
                'state' => 'active',
            ])->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors(['profession', 'profession_id']);

        $this->assertDatabaseMissing('users', [
            'first_name' => 'Pepe',
            'last_name' => 'Pérez',
            'email' => 'pepe@mail.es',
        ]);

        $this->assertDatabaseMissing('user_profiles', [
            'bio' => 'Programador de Laravel y Vue.js',
            'twitter' => 'https://twitter.com/pepe',
        ]);
    }

    /** @test */
    function the_profession_field_create_a_new_profession()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->from(route('users.edit', $user))
            ->put('usuarios/'. $user->id, $this->getValidData([
                'profession_id' => null,
                'profession' => 'Estudiante'
            ]))->assertRedirect(route('user.show', $user));

        $this->assertDatabaseCount('users', 1);

        $this->assertDatabaseHas('user_profiles', [
            'profession_id' => Profession::where('title', 'Estudiante')->first()->id,

        ]);

        $this->assertDatabaseHas('professions', [
            'id' => Profession::where('title', 'Estudiante')->first()->id,
            'title' => 'Estudiante',
        ]);
    }

    /** @test */
    function the_profession_field_cannot_create_a_new_profession_if_its_already_exists()
    {
        $this->withExceptionHandling();

        factory(Profession::class)->create([
            'title'=> 'Estudiante'
        ]);

        $user = factory(User::class)->create();

        $this->from(route('users.edit', $user))
            ->put('usuarios/' . $user->id, $this->getValidData([
                'profession_id' => null,
                'profession' => 'Estudiante'
            ]))->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors(['profession']);

        $this->assertDatabaseMissing('users', [
            'first_name' => 'Pepe'
        ]);

        $this->assertDatabaseMissing('user_profiles', ['bio' => 'Programador de Laravel y Vue.js',]);
    }

}
