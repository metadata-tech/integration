<div>
    <!-- Search and Add Role Button -->
    <div class="mb-4 flex justify-between items-center">
        <div class="flex-1 max-w-sm">
            <input
                type="text"
                wire:model.live="search"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Search roles..."
            >
        </div>
        <button
            wire:click="openModal('create')"
            class="ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
        >
            Add Role
        </button>
    </div>

    <!-- Roles Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            @foreach($roles as $role)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $role->name }}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($role->permissions as $permission)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $permission->name }}
                                    </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button
                            wire:click="openModal('edit', {{ $role->id }})"
                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                        >
                            Edit
                        </button>
                        @if($role->id !== 1)
                            <button
                                wire:click="deleteRole({{ $role->id }})"
                                wire:confirm="Are you sure you want to delete this role?"
                                class="text-red-600 hover:text-red-900"
                            >
                                Delete
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $roles->links() }}
    </div>

    <!-- Modal -->
    @if($isModalOpen)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50">
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        <div class="absolute right-0 top-0 pr-4 pt-4">
                            <button
                                wire:click="closeModal"
                                class="rounded-md bg-white text-gray-400 hover:text-gray-500"
                            >
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">
                                    {{ $modalMode === 'create' ? 'Add New Role' : 'Edit Role' }}
                                </h3>

                                <form wire:submit="create" class="mt-4 space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Role Name</label>
                                        <input
                                            type="text"
                                            id="name"
                                            wire:model="name"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                        @error('name')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                                        <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-2">
                                            @foreach($permissions as $permission)
                                                <div class="flex items-center py-1">
                                                    <input
                                                        type="checkbox"
                                                        id="permission_{{ $permission->id }}"
                                                        value="{{ $permission->name }}"
                                                        wire:model="selectedPermissions"
                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                    <label for="permission_{{ $permission->id }}" class="ml-2 text-sm text-gray-700">
                                                        {{ $permission->name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('selectedPermissions')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                        <button
                                            type="submit"
                                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                        >
                                            {{ $modalMode === 'create' ? 'Create' : 'Update' }}
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="closeModal"
                                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
