<?php

namespace App\Livewire\Admin\Permissions;

use Livewire\Component;
use Spatie\Permission\Models\Role;

class Roles extends Component
{
      public $view;
    public $roleId;
    public $roles;
    public $viewModal = false;
    public $search = '';

    public function render()
    {
        //check permissions
      if(!auth()->user()->can('role.view')) {
            return abort(403, 'Unauthorized action.');
        }

        if ($this->search) {
            $this->roles = Role::with('permissions')
                ->where('name', 'like', '%' . $this->search . '%')
                ->orWhereHas('permissions', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                })
                ->get();
        } else {
            $this->roles = Role::with('permissions')->get(); // Fetch all roles with their permissions from the database
        }

        // dd($this->roles); // Debugging line to check filtered roles

        return view('livewire.admin.permissions.roles')->layout('layouts.admin.admin');
    }
        public function openViewModal($id)
    {

        $this->roleId = $id; // Set the role ID for the modal
        $view = Role::with('permissions')->where('id', $id)->first();
        $this->view= $view->toArray(); // Assign the role to the component property
        $this->viewModal = true;
    }
    public function closeViewModal()
    {
        $this->viewModal = false; // Close the view modal
        // $this->reset(['roleId','viewRole']); // Reset form fields

    }
    public function deleteRole($id)
    {
     if(!auth()->user()->can('role.delete')) {
            return abort(403, 'Unauthorized action.');
        }
        $role = Role::find($id);
        if (!$role) {
            session()->flash('error', 'Role not found.');
            return;
        }
        $role->permissions()->detach();
        $role->delete();
        session()->flash('success', 'Role deleted successfully.');
        $this->render(); // Refresh the roles list
    }

}
