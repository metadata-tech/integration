<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserManagement extends Component
{
    use WithPagination;

    // Form properties
    public $userId;
    public $name;
    public $email;
    public $password;
    public $selectedRoles = [];

    // UI state properties
    public $isModalOpen = false;
    public $modalMode = 'create'; // create or edit
    public $search = '';

    // Validation rules
    protected $rules = [
        'name' => 'required|min:2',
        'email' => 'required|email',
        'selectedRoles' => 'array',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openModal($mode = 'create', $userId = null)
    {
        $this->resetValidation();
        $this->resetForm();
        $this->modalMode = $mode;

        if ($mode === 'edit' && $userId) {
            $this->userId = $userId;
            $user = User::findOrFail($userId);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->selectedRoles = $user->roles->pluck('name')->toArray();
        }

        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->selectedRoles = [];
    }

    public function create()
    {
        if ($this->modalMode === 'create') {
            $this->validate([
                'name' => 'required|min:2',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'selectedRoles' => 'array',
            ]);
        } else {
            $this->validate([
                'name' => 'required|min:2',
                'email' => 'required|email|unique:users,email,' . $this->userId,
                'password' => $this->password ? 'min:8' : '',
                'selectedRoles' => 'array',
            ]);
        }

        if ($this->modalMode === 'create') {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            $this->dispatch('notify', [
                'message' => 'User created successfully!',
                'type' => 'success'
            ]);
        } else {
            $user = User::findOrFail($this->userId);
            $userData = [
                'name' => $this->name,
                'email' => $this->email,
            ];

            if ($this->password) {
                $userData['password'] = Hash::make($this->password);
            }

            $user->update($userData);

            $this->dispatch('notify', [
                'message' => 'User updated successfully!',
                'type' => 'success'
            ]);
        }
        #dump($this->selectedRoles);
        // Sync roles
        $user->syncRoles($this->selectedRoles);

        $this->closeModal();
        $this->resetForm();
    }

    public function deleteUser($userId)
    {
        if (auth()->id() === (int)$userId) {
            $this->dispatch('notify', [
                'message' => 'You cannot delete yourself!',
                'type' => 'error'
            ]);
            return;
        }

        $user = User::findOrFail($userId);
        $user->delete();

        $this->dispatch('notify', [
            'message' => 'User deleted successfully!',
            'type' => 'success'
        ]);
    }

    public function render()
    {
        $users = User::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->paginate(10);

        $roles = Role::all();

        return view('livewire.admin.users.user-management', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }
}
