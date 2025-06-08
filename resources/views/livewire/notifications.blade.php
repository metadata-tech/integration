<div
    class="fixed top-4 right-4 z-50 space-y-4 min-w-[320px]"
    x-data="{
        notifications: $wire.entangle('notifications').live,
        remove(notification) {
            $wire.remove(notification.id)
        }
    }"
>
    <template x-for="notification in notifications" :key="notification.id">
        <div
            x-show="true"
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5"
            x-init="setTimeout(() => remove(notification), 3000)"
        >
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <!-- Success Icon -->
                        <template x-if="notification.type === 'success'">
                            <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </template>
                        <!-- Error Icon -->
                        <template x-if="notification.type === 'error'">
                            <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                        </template>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <p
                            x-text="notification.message"
                            :class="{
                                'text-green-900': notification.type === 'success',
                                'text-red-900': notification.type === 'error'
                            }"
                            class="text-sm font-medium leading-5"
                        ></p>
                    </div>
                    <div class="ml-4 flex flex-shrink-0">
                        <button
                            @click="remove(notification)"
                            class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
