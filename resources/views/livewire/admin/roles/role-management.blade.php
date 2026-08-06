<div>
    <!-- Search and Add Role Button -->
    <div class="mb-4 flex items-center justify-between gap-4">
        <div class="max-w-sm flex-1">
            <x-input
                type="text"
                wire:model.live="search"
                class="w-full"
                placeholder="Cari peranan..."
            />
        </div>
        <x-button type="button" wire:click="openModal('create')">
            Tambah Peranan
        </x-button>
    </div>

    <!-- Roles Table -->
    <div class="myds-card overflow-x-auto">
        <table class="myds-table">
            <thead>
            <tr>
                <th>Nama</th>
                <th>Kebenaran</th>
                <th>Tindakan</th>
            </tr>
            </thead>
            <tbody>
            @foreach($roles as $role)
                <tr class="hover:bg-washed-100/60">
                    <td class="whitespace-nowrap font-medium text-gray-900">{{ $role->name }}</td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            @foreach($role->permissions as $permission)
                                <span class="myds-badge-success">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="whitespace-nowrap text-sm">
                        <span class="flex items-center gap-3">
                            <button
                                wire:click="openModal('edit', {{ $role->id }})"
                                class="font-medium text-primary-600 hover:text-primary-700 hover:underline"
                            >
                                Edit
                            </button>
                            @if($role->id !== 1)
                                <button
                                    wire:click="deleteRole({{ $role->id }})"
                                    wire:confirm="Are you sure you want to delete this role?"
                                    class="font-medium text-danger-600 hover:text-danger-700 hover:underline"
                                >
                                    Padam
                                </button>
                            @endif
                        </span>
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
        <div class="fixed inset-0 z-50 bg-gray-900/50 transition-opacity">
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-context-menu transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
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
                            <div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
                                <h3 class="font-heading text-lg font-semibold leading-6 text-gray-900">
                                    {{ $modalMode === 'create' ? 'Tambah Peranan Baharu' : 'Kemas Kini Peranan' }}
                                </h3>

                                <form wire:submit="create" class="mt-4 space-y-4 text-start">
                                    <div>
                                        <x-label for="name" value="Nama Peranan" />
                                        <x-input type="text" id="name" wire:model="name" class="mt-1 block w-full" />
                                        @error('name')
                                        <span class="mt-1 text-xs text-danger-600">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <x-label value="Kebenaran" class="mb-2" />
                                        <div class="max-h-60 overflow-y-auto rounded-lg border border-outline-300 p-3">
                                            @foreach($permissions as $permission)
                                                <label for="permission_{{ $permission->id }}" class="flex items-center gap-2 py-1 text-sm text-gray-700">
                                                    <x-checkbox
                                                        id="permission_{{ $permission->id }}"
                                                        value="{{ $permission->name }}"
                                                        wire:model="selectedPermissions"
                                                    />
                                                    {{ $permission->name }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('selectedPermissions')
                                        <span class="mt-1 text-xs text-danger-600">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="mt-5 gap-2 sm:mt-4 sm:flex sm:flex-row-reverse">
                                        <x-button type="submit" class="w-full sm:w-auto">
                                            {{ $modalMode === 'create' ? 'Simpan' : 'Kemas Kini' }}
                                        </x-button>
                                        <x-secondary-button type="button" wire:click="closeModal" class="mt-3 w-full sm:mt-0 sm:w-auto">
                                            Batal
                                        </x-secondary-button>
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
