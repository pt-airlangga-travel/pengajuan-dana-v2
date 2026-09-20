<?php

namespace App\Filament\Pages\Auth;

use Caresome\FilamentAuthDesigner\Pages\Auth\Login as BaseLogin;
// use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use MarcoGermani87\FilamentCaptcha\Forms\Components\CaptchaField;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                $this->getLoginFormComponent()->extraAttributes(['class' => 'py-1']),
                $this->getPasswordFormComponent()->extraAttributes(['class' => 'py-1']),
                CaptchaField::make('captcha'),
            ])
            ->statePath('data');
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Email atau Username')
            ->required()
            ->autofocus()
            ->prefixIcon('heroicon-o-user')
            ->extraAttributes([
                'class' => 'rounded-xl shadow-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500',
                'tabindex' => 1,
            ])
            ->autocomplete();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login_type = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $login_type => $data['login'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            // 'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
            'data.login' => 'Login failed. Please check your email or Employee ID (NIP) and password.',
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        // Legacy DB hanya punya id_role char3 + active_status, bukan pivot roles
        // Load roles (hasMany via role_defined_id) untuk compat, tapi fallback ke single role
        $user = Auth::user();
        if ($user) {
            try {
                $user->loadMissing('roles');
            } catch (\Throwable $e) {
                // ignore
            }
            // Fallback: jika roles kosong tapi role ada, anggap roles = [role]
            if ($user->relationLoaded('roles') && $user->roles->isEmpty() && $user->role) {
                $user->setRelation('roles', collect([$user->role]));
            }
        }

        if (! $user || ! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'data.login' => 'Akun Anda tidak aktif.',
            ]);
        }

        // Jika masih pakai legacy single-role, cukup cek role ada
        $roles = $user->roles ?? collect($user->role ? [$user->role] : []);

        if ($roles->isEmpty()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'data.login' => 'Akun tidak memiliki akses yang ditetapkan.',
            ]);
        }

        $activeRoles = $roles->where('is_active', true);

        if ($activeRoles->isEmpty()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'data.login' => 'Tidak ada akses aktif yang ditetapkan untuk akun ini.',
            ]);
        }

        // active_role_id tidak ada di schema legacy (hanya id_role), jadi skip update jika kolom tidak ada
        try {
            $selectedRole = $roles->firstWhere('id', $user->active_role_id);
            if (! $selectedRole || ! $selectedRole->is_active) {
                // hanya update jika kolom ada di DB
                $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'active_role_id');
                if ($hasColumn) {
                    $user->update([
                        'active_role_id' => $activeRoles->first()->id,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // legacy: ignore active_role_id errors
        }

        return $response;
    }
}
