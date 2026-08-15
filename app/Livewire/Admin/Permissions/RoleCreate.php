<?php

namespace App\Livewire\Admin\Permissions;

use App\Services\RoleService;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class RoleCreate extends Component
{
    public int    $roleId      = 0;
    public string $name        = '';
    public array  $permissions = [];
    public bool   $selectAll   = false;

    public function mount(int $id = 0, RoleService $roleService): void
    {
        if ($id > 0) {
            if (! auth()->user()->can('role.edit')) {
                abort(403, 'Unauthorized action.');
            }

            $role = $roleService->find($id);

            if (! $role) {
                abort(404);
            }

            $this->roleId      = $role->id;
            $this->name        = $role->name;
            $this->permissions = $role->permissions->pluck('name')->toArray();
            $this->selectAll   = count($this->permissions) === Permission::count();
        } else {
            if (! auth()->user()->can('role.create')) {
                abort(403, 'Unauthorized action.');
            }
        }
    }

    public function updatedSelectAll(): void
    {
        $this->permissions = $this->selectAll
            ? Permission::pluck('name')->toArray()
            : [];
    }

    public function toggleModule(array $modulePermissions): void
    {
        $allSelected = count(array_intersect($modulePermissions, $this->permissions)) === count($modulePermissions);

        $this->permissions = $allSelected
            ? array_values(array_diff($this->permissions, $modulePermissions))
            : array_values(array_unique(array_merge($this->permissions, $modulePermissions)));

        $this->selectAll = count($this->permissions) === Permission::count();
    }

    public function updatedPermissions(): void
    {
        $this->selectAll = count($this->permissions) === Permission::count();
    }

    public function save(RoleService $roleService)
    {
        if ($this->roleId > 0) {
            $this->validate(['name' => 'required|string|unique:roles,name,' . $this->roleId]);

            $role = $roleService->find($this->roleId);

            if (! $role) {
                abort(404);
            }

            $roleService->update($role, $this->name, $this->permissions);

            return redirect()->route('admin.roles.list')->with('success', 'Role updated successfully.');
        }

        $this->validate(['name' => 'required|string|unique:roles,name']);

        $roleService->create($this->name, $this->permissions);

        return redirect()->route('admin.roles.list')->with('success', 'Role created successfully.');
    }

    public function render(RoleService $roleService)
    {
        [$grouped] = $roleService->allPermissionsGrouped();

        return view('livewire.admin.permissions.role-create', [
            'permissionsGrouped' => $grouped,
        ])->layout('layouts.admin.admin');
    }
}
