<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <!-- Source Languages -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Source Languages') }}</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $sourceLanguageCount }}</div>
                        @if($activeSource)
                            <div class="mt-1 text-sm text-indigo-600 dark:text-indigo-400">
                                {{ __('Active:') }} {{ $activeSource->language->name }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Target Languages -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Target Languages') }}</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $targetLanguageCount }}</div>
                    </div>
                </div>

                <!-- Total Translations -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Translations') }}</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $translationCount }}</div>
                    </div>
                </div>

                <!-- Words to Review -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg {{ $dueForReviewCount > 0 ? 'ring-2 ring-indigo-500' : '' }}">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Words to Review') }}</div>
                                <div class="mt-1 text-3xl font-semibold {{ $dueForReviewCount > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-900 dark:text-gray-100' }}">
                                    {{ $dueForReviewCount }}
                                </div>
                            </div>
                            @if($dueForReviewCount > 0)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($dueForReviewCount > 0)
                <!-- Words to Review Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">{{ __('Words Due for Review') }}</h3>
                            <form action="{{ route('review.start') }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                    </svg>
                                    {{ __('Start Review') }}
                                </button>
                            </form>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($dueTranslations as $translation)
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ Str::limit($translation->source_text, 30) }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        → {{ Str::limit($translation->target_text, 30) }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        {{ $translation->targetLanguage->name }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($dueForReviewCount > 10)
                            <div class="mt-4 text-center">
                                <a href="{{ route('review.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ __('View all :count due translations', ['count' => $dueForReviewCount]) }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($totalTranslationsForSource > 0)
                <!-- No Due Words, But Can Practice -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900 dark:text-gray-100 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">{{ __('All caught up!') }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            {{ __('You have no words due for review right now.') }}
                        </p>
                        <form action="{{ route('review.start') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="practice_all" value="1">
                            <button type="submit" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2 mx-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                </svg>
                                {{ __('Practice (10 words)') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">{{ __('Quick Actions') }}</h3>
                    <div class="flex flex-wrap gap-4">
                        @if($sourceLanguageCount === 0)
                            <a href="{{ route('languages.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Set Up Languages') }}
                            </a>
                        @else
                            <a href="{{ route('translations.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('New Translation') }}
                            </a>
                            <a href="{{ route('translations.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('View Translations') }}
                            </a>
                            <a href="{{ route('languages.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Manage Languages') }}
                            </a>
                            @if($dueForReviewCount > 0)
                                <a href="{{ route('review.index') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    {{ __('Review Words') }}
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
