<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('My Languages') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Source Languages -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">{{ __('Source Languages (Native)') }}</h3>

                    <!-- Add Source Language -->
                    <form method="POST" action="{{ route('languages.source.store') }}" class="flex items-end gap-4 mb-6">
                        @csrf
                        <div class="flex-1">
                            <label for="source_language_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Add a source language') }}
                            </label>
                            <select name="language_id" id="source_language_id"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <option value="">{{ __('Select language...') }}</option>
                                @foreach($languages as $language)
                                    <option value="{{ $language->id }}">
                                        {{ $language->name }} {{ $language->native_name ? '(' . $language->native_name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('language_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            {{ __('Add') }}
                        </button>
                    </form>

                    <!-- Source Language List -->
                    <div class="flex flex-wrap gap-3">
                        @forelse($sourceLanguages as $source)
                            <div class="flex items-center gap-2 px-4 py-2 rounded-lg border {{ $source->is_active ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-300 dark:border-gray-600' }}">
                                <span class="font-medium">{{ $source->language->name }}</span>
                                @if($source->is_active)
                                    <span class="text-xs bg-indigo-500 text-white px-2 py-0.5 rounded-full">{{ __('Active') }}</span>
                                @else
                                    <form method="POST" action="{{ route('languages.source.switch', $source->id) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ __('Switch') }}
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('languages.source.destroy', $source->id) }}" class="inline"
                                      onsubmit="return confirm('This will also remove associated target languages and translations. Continue?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs">
                                        &times;
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400">{{ __('No source languages added yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Target Languages -->
            @if($activeSource)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">
                        {{ __('Target Languages for') }} <span class="text-indigo-600 dark:text-indigo-400">{{ $activeSource->language->name }}</span>
                    </h3>

                    <!-- Add Target Language -->
                    <form method="POST" action="{{ route('languages.target.store') }}" class="flex items-end gap-4 mb-6">
                        @csrf
                        <input type="hidden" name="source_language_id" value="{{ $activeSource->language_id }}">
                        <div class="flex-1">
                            <label for="target_language_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Add a target language') }}
                            </label>
                            <select name="target_language_id" id="target_language_id"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <option value="">{{ __('Select language...') }}</option>
                                @foreach($languages as $language)
                                    @if($language->id !== $activeSource->language_id)
                                        <option value="{{ $language->id }}">
                                            {{ $language->name }} {{ $language->native_name ? '(' . $language->native_name . ')' : '' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('target_language_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            {{ __('Add') }}
                        </button>
                    </form>

                    <!-- Target Language List -->
                    <div class="flex flex-wrap gap-3">
                        @forelse($targetLanguages as $target)
                            <div class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600">
                                <span class="font-medium">{{ $target->targetLanguage->name }}</span>
                                <form method="POST" action="{{ route('languages.target.destroy', $target->id) }}" class="inline"
                                      onsubmit="return confirm('Remove this target language?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs">
                                        &times;
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400">{{ __('No target languages added yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
