<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\Role;
use Illuminate\Support\Facades\Auth;

/**
 * Trait untuk 1 prefix /dashboard tapi navigasi per-role (Opsi B).
 * Pakai di tiap Resource: shouldRegisterNavigation() + canAccess logic.
 */
trait HasRoleNavigation
{
    /**
     * @param Role[] $roles
     */
    protected static function hasAnyRole(array $roles): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        return $user->hasAnyRole($roles);
    }

    protected static function hasRole(Role $role): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        return $user->hasRole($role);
    }
}
