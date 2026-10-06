<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_area_requires_authentication_and_admin_role(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect('/login');

        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($regularUser)->get(route('admin.users.create'))->assertForbidden();
    }

    public function test_admin_can_list_and_search_users(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Boss', 'is_admin' => true]);
        $user1 = User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@example.com']);
        $user2 = User::factory()->create(['name' => 'Carlos Pereira', 'email' => 'carlos@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk()
            ->assertSee('Admin Boss')
            ->assertSee('Ana Silva')
            ->assertSee('Carlos Pereira');

        $searchResponse = $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'carlos']));
        $searchResponse->assertOk()
            ->assertSee('Carlos Pereira')
            ->assertDontSee('Ana Silva');
    }

    public function test_admin_can_create_a_new_user_and_verification_notification_is_sent(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Novo Usuário',
            'email' => 'novo@releva.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'is_admin' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status');

        $newUser = User::where('email', 'novo@releva.test')->firstOrFail();
        $this->assertSame('Novo Usuário', $newUser->name);
        $this->assertTrue($newUser->is_admin);
        $this->assertNull($newUser->email_verified_at);
        $this->assertTrue(Hash::check('secret1234', $newUser->password));

        Notification::assertSentTo($newUser, VerifyEmail::class);
    }

    public function test_admin_cannot_create_user_with_duplicate_email(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['email' => 'existente@releva.test']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Outro Usuário',
            'email' => 'existente@releva.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create([
            'name' => 'Nome Antigo',
            'password' => Hash::make('senha-antiga-123'),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'Nome Atualizado',
            'email' => $target->email,
            'password' => '',
            'password_confirmation' => '',
            'is_admin' => '0',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $target->refresh();

        $this->assertSame('Nome Atualizado', $target->name);
        $this->assertTrue(Hash::check('senha-antiga-123', $target->password));
    }

    public function test_updating_email_resets_verification_and_resends_notification(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create([
            'email' => 'antigo@releva.test',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => 'atualizado@releva.test',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $target->refresh();

        $this->assertSame('atualizado@releva.test', $target->email);
        $this->assertNull($target->email_verified_at);

        Notification::assertSentTo($target, VerifyEmail::class);
    }

    public function test_admin_cannot_remove_their_own_admin_access(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_admin' => '0',
        ]);

        $response->assertSessionHasErrors(['is_admin']);
        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $target));
        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_can_resend_verification_notification(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $unverified = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($admin)->post(route('admin.users.resend-verification', $unverified));
        $response->assertRedirect(route('admin.users.index'));

        Notification::assertSentTo($unverified, VerifyEmail::class);
    }
}
