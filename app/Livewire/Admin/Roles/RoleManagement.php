<?php

namespace App\Livewire\Admin\Roles;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagement extends Component
{
    use WithPagination;

    // Form properties
    public $roleId;
    public $name;
    public $selectedPermissions = [];

    // UI state properties
    public $isModalOpen = false;
    public $modalMode = 'create'; // create or edit
    public $search = '';

    // Validation rules
    protected $rules = [
        'name' => 'required|min:2',
        'selectedPermissions' => 'array',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openModal($mode = 'create', $roleId = null)
    {
        $this->resetValidation();
        $this->resetForm();
        $this->modalMode = $mode;

        if ($mode === 'edit' && $roleId) {
            $this->roleId = $roleId;
            $role = Role::findOrFail($roleId);
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        }

        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function resetForm()
    {
        $this->roleId = null;
        $this->name = '';
        $this->selectedPermissions = [];
    }

    public function create()
    {
        if ($this->modalMode === 'create') {
            $this->validate([
                'name' => 'required|min:2|unique:roles,name',
                'selectedPermissions' => 'array',
            ]);
        } else {
            $this->validate([
                'name' => 'required|min:2|unique:roles,name,' . $this->roleId,
                'selectedPermissions' => 'array',
            ]);
        }

        if ($this->modalMode === 'create') {
            $role = Role::create(['name' => $this->name]);

            $this->dispatch('notify', [
                'message' => 'Role created successfully!',
                'type' => 'success'
            ]);
        } else {
            $role = Role::findOrFail($this->roleId);
            $role->name = $this->name;
            $role->save();

            $this->dispatch('notify', [
                'message' => 'Role '.$role->name.' updated successfully!',
                'type' => 'success'
            ]);
        }
        #dd($this->selectedPermissions);
        // Sync permissions
        $role->syncPermissions($this->selectedPermissions);
        //$role->syncPermissions(["2", "3"]);

        $this->closeModal();
        $this->resetForm();
    }

    public function deleteRole($roleId)
    {
        if (in_array($roleId, [1])) { // Assuming role ID 1 is admin/super-admin
            $this->dispatch('notify', [
                'message' => 'Cannot delete protected roles!',
                'type' => 'error'
            ]);
            return;
        }

        $role = Role::findOrFail($roleId);
        $role->delete();

        $this->dispatch('notify', [
            'message' => 'Role deleted successfully!',
            'type' => 'success'
        ]);
    }

    public function render()
    {
        $roles = Role::where('name', 'like', '%' . $this->search . '%')
            ->paginate(10);

        $permissions = Permission::all();

        return view('livewire.admin.roles.role-management', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
