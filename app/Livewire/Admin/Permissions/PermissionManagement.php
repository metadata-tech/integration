<?php

namespace App\Livewire\Admin\Permissions;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class PermissionManagement extends Component
{
    use WithPagination;

    // Form properties
    public $permissionId;
    public $name;

    // UI state properties
    public $isModalOpen = false;
    public $modalMode = 'create'; // create or edit
    public $search = '';

    // Validation rules
    protected $rules = [
        'name' => 'required|min:2',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openModal($mode = 'create', $permissionId = null)
    {
        $this->resetValidation();
        $this->resetForm();
        $this->modalMode = $mode;

        if ($mode === 'edit' && $permissionId) {
            $this->permissionId = $permissionId;
            $permission = \Spatie\Permission\Models\Permission::findOrFail($permissionId);
            $this->name = $permission->name;
        }

        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function resetForm()
    {
        $this->permissionId = null;
        $this->name = '';
    }

    public function create()
    {
        if ($this->modalMode === 'create') {
            $this->validate([
                'name' => 'required|min:2|unique:permissions,name',
            ]);

            \Spatie\Permission\Models\Permission::create(['name' => $this->name]);

            $this->dispatch('notify', [
                'message' => 'Permission created successfully!',
                'type' => 'success'
            ]);
        } else {
            $this->validate([
                'name' => 'required|min:2|unique:permissions,name,' . $this->permissionId,
            ]);

            $permission = \Spatie\Permission\Models\Permission::findOrFail($this->permissionId);
            $permission->name = $this->name;
            $permission->save();

            $this->dispatch('notify', [
                'message' => 'Permission updated successfully!',
                'type' => 'success'
            ]);
        }

        $this->closeModal();
        $this->resetForm();
    }

    public function deletePermission($permissionId)
    {
        ray($permissionId);
        $permission = \Spatie\Permission\Models\Permission::findOrFail($permissionId);
        ray($permission);

        // Check if any roles are using this permission
        if ($permission->roles()->count() > 0) {
            $this->dispatch('notify', [
                'message' => 'Cannot delete permission that is assigned to roles!',
                'type' => 'error'
            ]);
            return;
        }

        $permission->delete();

        $this->dispatch('notify', [
            'message' => 'Permission deleted successfully!',
            'type' => 'success'
        ]);
    }

    public function render()
    {
        $permissions = \Spatie\Permission\Models\Permission::where('name', 'like', '%' . $this->search . '%')
            ->paginate(10);

        return view('livewire.admin.permissions.permission-management', [
            'permissions' => $permissions,
        ]);
    }
}
