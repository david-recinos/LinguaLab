<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('User Details') }}
            </h2>
            <a href="{{ route('users.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                {{ __('Back to List') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4">User Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</p>
                                <p class="mt-1">{{ $user->id }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</p>
                                <p class="mt-1">{{ $user->name }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</p>
                                <p class="mt-1">{{ $user->email }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</p>
                                <p class="mt-1 capitalize">{{ $user->roles->pluck('name')->implode(', ') ?: 'No role' }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Verified At</p>
                                <p class="mt-1">{{ $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'Not verified' }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</p>
                                <p class="mt-1">{{ $user->created_at->format('Y-m-d H:i:s') }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated At</p>
                                <p class="mt-1">{{ $user->updated_at->format('Y-m-d H:i:s') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-2">
                        <a href="{{ route('users.edit', $user) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                            Edit User
                        </a>
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Delete User
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
