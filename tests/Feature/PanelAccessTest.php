<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Livewire\Livewire;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    #[Test]
    public function guests_use_the_existing_login(): void
    {
        $this->withExceptionHandling();

        $this->get('/admin/panel')->assertRedirect(route('login'));
    }

    #[Test]
    public function active_administrators_can_use_the_panel_and_legacy_pages(): void
    {
        $this->login(User::factory()->admin()->create());

        $this->get('/admin/panel')->assertOk()->assertSee('Administration')
            ->assertSee(Vite::asset('resources/css/admin.css'), false)
            ->assertDontSee(Vite::asset('resources/js/app.ts'), false);
        $this->get('/profile')->assertOk()->assertSee(Vite::asset('resources/js/app.ts'), false)
            ->assertDontSee(Vite::asset('resources/css/admin.css'), false)
            ->assertDontSee('/js/filament/', false);
        $this->get('/admin/users')->assertOk()->assertSee('admin-navigation', false);
    }

    #[Test]
    #[TestWith([false, false])]
    #[TestWith([false, true])]
    #[TestWith([true, true])]
    public function users_and_deactivated_accounts_cannot_enter_the_panel(bool $admin, bool $disabled): void
    {
        $user = User::factory()->create(['is_admin' => $admin]);
        $this->login($user)->withExceptionHandling();
        if ($disabled) {
            User::whereKey($user->id)->delete();
        }

        $this->get('/admin/panel')->assertForbidden();
    }

    #[Test]
    #[TestWith(['guest'])]
    #[TestWith(['user'])]
    #[TestWith(['disabled-user'])]
    #[TestWith(['disabled-admin'])]
    #[TestWith(['demoted-admin'])]
    public function a_valid_panel_snapshot_is_refused_after_session_or_permission_changes(string $identity): void
    {
        $admin = User::factory()->admin()->create();
        $this->login($admin);
        $snapshot = $this->panelSnapshot();
        if ($identity === 'guest') {
            auth()->guard('web')->logout();
        } elseif ($identity === 'demoted-admin') {
            User::whereKey($admin->id)->update(['is_admin' => false]);
        } else {
            $user = User::factory()->create(['is_admin' => $identity === 'disabled-admin']);
            $this->login($user);
            if (str_starts_with($identity, 'disabled-')) {
                User::whereKey($user->id)->delete();
            }
        }
        $this->withExceptionHandling();

        $response = $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), [
            'components' => [['snapshot' => $snapshot, 'updates' => [], 'calls' => []]],
        ]);

        $response->assertStatus($identity === 'guest' ? 401 : 403);
        $response->assertDontSee('Offene Sammelbestellungen');
    }

    #[Test]
    public function an_active_administrator_can_update_the_panel_via_livewire(): void
    {
        $this->loginAsAdmin();
        $snapshot = $this->panelSnapshot();

        $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), [
            'components' => [['snapshot' => $snapshot, 'updates' => [], 'calls' => []]],
        ])->assertOk()->assertJsonStructure(['components' => [['snapshot', 'effects']]]);
    }

    #[Test]
    public function package_download_endpoints_use_the_existing_login_before_binding_data(): void
    {
        $this->withExceptionHandling();

        $this->get(route('filament.exports.download', ['export' => 1]))->assertRedirect(route('login'));
        $this->get(route('filament.imports.failed-rows.download', ['import' => 1]))->assertRedirect(route('login'));
    }

    #[Test]
    #[TestWith([false, false])]
    #[TestWith([false, true])]
    #[TestWith([true, true])]
    public function package_download_endpoints_apply_the_admin_boundary_before_binding_data(bool $admin, bool $disabled): void
    {
        $user = User::factory()->create(['is_admin' => $admin]);
        $this->login($user)->withExceptionHandling();
        if ($disabled) {
            User::whereKey($user->id)->delete();
        }

        $this->getJson(route('filament.exports.download', ['export' => 1]))->assertForbidden();
        $this->getJson(route('filament.imports.failed-rows.download', ['import' => 1]))->assertForbidden();
    }

    private function panelSnapshot(): string
    {
        $html = $this->get('/admin/panel')->assertOk()->getContent();
        preg_match('/wire:snapshot="([^"]+)"/', $html, $match);
        $this->assertArrayHasKey(1, $match, 'The rendered panel must contain a signed Livewire snapshot.');

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
    }
}
