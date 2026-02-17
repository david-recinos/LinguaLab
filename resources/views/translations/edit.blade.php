<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Translation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100" x-data="{ type: '{{ old('type', $translation->type) }}' }">
                    <form method="POST" action="{{ route('translations.update', $translation) }}">
                        @csrf
                        @method('PUT')

                        <!-- Target Language -->
                        <div class="mb-4">
                            <label for="target_language_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Target Language') }}
                            </label>
                            <select name="target_language_id" id="target_language_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                @foreach($targetLanguages as $tl)
                                    <option value="{{ $tl->target_language_id }}" {{ old('target_language_id', $translation->target_language_id) == $tl->target_language_id ? 'selected' : '' }}>
                                        {{ $tl->targetLanguage->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('target_language_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Type -->
                        <div class="mb-4">
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Type') }}
                            </label>
                            <select name="type" id="type" x-model="type"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <option value="word">{{ __('Word') }}</option>
                                <option value="text">{{ __('Text') }}</option>
                                <option value="expression">{{ __('Expression') }}</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Word Type (conditional) -->
                        <div class="mb-4" x-show="type === 'word'" x-transition>
                            <label for="word_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Word Type') }}
                            </label>
                            <select name="word_type_id" id="word_type_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Select word type...') }}</option>
                                @foreach($wordTypes as $wordType)
                                    <option value="{{ $wordType->id }}" {{ old('word_type_id', $translation->word_type_id) == $wordType->id ? 'selected' : '' }}>
                                        {{ $wordType->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('word_type_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Source Text -->
                        <div class="mb-4">
                            <label for="source_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Source Text') }}
                            </label>
                            <textarea name="source_text" id="source_text" rows="2"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      required>{{ old('source_text', $translation->source_text) }}</textarea>
                            @error('source_text')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Target Text -->
                        <div class="mb-4">
                            <label for="target_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Target Text') }}
                            </label>
                            <textarea name="target_text" id="target_text" rows="2"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      required>{{ old('target_text', $translation->target_text) }}</textarea>
                            @error('target_text')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Pronunciation -->
                        <div class="mb-4">
                            <label for="pronunciation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Pronunciation') }}
                            </label>
                            <input type="text" name="pronunciation" id="pronunciation" value="{{ old('pronunciation', $translation->pronunciation) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('pronunciation')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Example Sentence -->
                        <div class="mb-4">
                            <label for="example_sentence" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Example Sentence') }}
                            </label>
                            <textarea name="example_sentence" id="example_sentence" rows="2"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('example_sentence', $translation->example_sentence) }}</textarea>
                            @error('example_sentence')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Notes') }}
                            </label>
                            <textarea name="notes" id="notes" rows="2"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $translation->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('translations.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Update Translation') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
