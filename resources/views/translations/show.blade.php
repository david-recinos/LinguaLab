<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Translation Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Source -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">
                                {{ __('Source') }} ({{ $translation->sourceLanguage->name }})
                            </h3>
                            <p class="text-lg">{{ $translation->source_text }}</p>
                        </div>

                        <!-- Target -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">
                                {{ __('Target') }} ({{ $translation->targetLanguage->name }})
                            </h3>
                            <p class="text-lg">{{ $translation->target_text }}</p>
                        </div>

                        <!-- Type -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Type') }}</h3>
                            <p>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $translation->type === 'word' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                    {{ $translation->type === 'text' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                    {{ $translation->type === 'expression' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}">
                                    {{ ucfirst($translation->type) }}
                                </span>
                                @if($translation->type === 'word' && $translation->wordType)
                                    <span class="text-gray-500 dark:text-gray-400 ml-1">({{ $translation->wordType->name }})</span>
                                @endif
                            </p>
                        </div>

                        <!-- Pronunciation -->
                        @if($translation->pronunciation)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Pronunciation') }}</h3>
                            <p>{{ $translation->pronunciation }}</p>
                        </div>
                        @endif

                        <!-- Example Sentence -->
                        @if($translation->example_sentence)
                        <div class="md:col-span-2">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Example Sentence') }}</h3>
                            <p>{{ $translation->example_sentence }}</p>
                        </div>
                        @endif

                        <!-- Notes -->
                        @if($translation->notes)
                        <div class="md:col-span-2">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Notes') }}</h3>
                            <p>{{ $translation->notes }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('translations.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                            {{ __('Back') }}
                        </a>
                        <a href="{{ route('translations.edit', $translation) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded mr-2">
                            {{ __('Edit') }}
                        </a>
                        <form action="{{ route('translations.destroy', $translation) }}" method="POST" class="inline"
                              onsubmit="return confirm('Delete this translation?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
