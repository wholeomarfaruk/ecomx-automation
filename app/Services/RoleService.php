<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function all(string $search = '')
    {
        return Role::with('permissions')
            ->when($search, fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('permissions', fn($pq) => $pq->where('name', 'like', "%{$search}%"))
            )
            ->get();
    }

    public function find(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    public function allPermissionsGrouped(): array
    {
        $permissions = Permission::all()->map(function ($item) {
            $item->group_name = explode('.', $item->name)[0];
            return $item;
        })->groupBy('group_name');

        return [$permissions, Permission::all()];
    }

    public function create(string $name, array $permissions): Role
    {
        $role = Role::create(['name' => $name]);
        $role->syncPermissions($permissions);

        activity('permissions')
            ->causedBy(Auth::user())
            ->performedOn($role)
            ->withProperties(['permissions' => $permissions])
            ->event('created')
            ->log("Role \"{$name}\" was created with " . count($permissions) . ' permission(s)');

        return $role;
    }

    public function update(Role $role, string $name, array $permissions): Role
    {
        $oldName        = $role->name;
        $oldPermissions = $role->permissions->pluck('name')->sort()->values()->toArray();
        $newPermissions = collect($permissions)->sort()->values()->toArray();

        $role->name = $name;
        $role->save();
        $role->syncPermissions($permissions);

        activity('permissions')
            ->causedBy(Auth::user())
            ->performedOn($role)
            ->withProperties([
                'old'        => ['name' => $oldName,  'permissions' => $oldPermissions],
                'attributes' => ['name' => $name,     'permissions' => $newPermissions],
            ])
            ->event('updated')
            ->log("Role \"{$name}\" was updated");

        return $role;
    }

    public function delete(Role $role): void
    {
        $name        = $role->name;
        $permissions = $role->permissions->pluck('name')->toArray();

        activity('permissions')
            ->causedBy(Auth::user())
            ->withProperties(['name' => $name, 'permissions' => $permissions])
            ->event('deleted')
            ->log("Role \"{$name}\" was deleted");

        $role->permissions()->detach();
        $role->delete();
    }
}
