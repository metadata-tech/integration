

        <x-slot name="title">
            {{ __('Create API Token') }}
        </x-slot>

        <x-slot name="description">
            {{ __('API tokens allow third-party services to authenticate with our application on your behalf.') }}
        </x-slot>
<div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
    <div>
        <!-- Token creation form -->
        <form method="POST" action="{{ route('admin.user-tokens.store', $user) }}">
            @csrf
            <div class="mb-4">
                <x-label for="name" value="{{ __('Token Name') }}" />
                <x-input id="name" type="text" class="mt-1 block w-full" name="name" required autofocus />
            </div>

            <!-- Permissions checkboxes -->
            @if (Laravel\Jetstream\Jetstream::hasPermissions())
                <x-label value="{{ __('Permissions') }}" />
                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach (Laravel\Jetstream\Jetstream::$permissions as $permission)
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="{{ $permission }}">
                            <span class="ml-2 text-sm text-gray-600">{{ $permission }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="mt-4">
                <x-button>Create Token</x-button>
            </div>
        </form>
    </div>

    <!-- Existing tokens -->
    @if ($user->tokens->isNotEmpty())
        <div class="mt-8 space-y-3">
            @foreach ($user->tokens as $token)
                <div class="flex items-center justify-between">
                    <div>{{ $token->name }}</div>
                    <div class="flex space-x-2">
                        <form method="POST" action="{{ route('admin.user-tokens.update', [$user, $token->id]) }}">
                            @csrf @method('PUT')
                            <button type="submit" class="text-gray-600 hover:text-gray-900">
                                {{ __('Permissions') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.user-tokens.destroy', [$user, $token->id]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
