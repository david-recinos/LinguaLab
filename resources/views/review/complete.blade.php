<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Review Complete') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <!-- Summary Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center">
                    <!-- Completion Icon -->
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-indigo-100 dark:bg-indigo-900/30 mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        {{ __('Session Complete!') }}
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8">
                        {{ __('Great job practicing your vocabulary.') }}
                    </p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-4 gap-4 mb-8">
                        <!-- Total -->
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $summary['total'] }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total') }}</div>
                        </div>

                        <!-- Correct -->
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                {{ $summary['correct'] }}
                            </div>
                            <div class="text-sm text-green-600 dark:text-green-400">{{ __('Correct') }}</div>
                        </div>

                        <!-- Wrong -->
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                                {{ $summary['wrong'] }}
                            </div>
                            <div class="text-sm text-red-600 dark:text-red-400">{{ __('Wrong') }}</div>
                        </div>

                        <!-- Skipped -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ $summary['skipped'] }}
                            </div>
                            <div class="text-sm text-yellow-600 dark:text-yellow-400">{{ __('Skipped') }}</div>
                        </div>
                    </div>

                    <!-- Accuracy -->
                    <div class="mb-8">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('Accuracy') }}</div>
                        <div class="relative h-4 bg-gray-200 dark:bg-gray-700 rounded-full max-w-xs mx-auto">
                            <div class="absolute h-4 bg-gradient-to-r from-red-500 via-yellow-500 to-green-500 rounded-full" style="width: {{ $summary['accuracy'] }}%"></div>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                            {{ $summary['accuracy'] }}%
                        </div>
                    </div>

                    @if($summary['total_time_seconds'] > 0)
                        <!-- Time Spent -->
                        <div class="text-gray-500 dark:text-gray-400 mb-8">
                            {{ __('Time spent:') }}
                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                @php
                                    $minutes = floor($summary['total_time_seconds'] / 60);
                                    $seconds = $summary['total_time_seconds'] % 60;
                                @endphp
                                @if($minutes > 0)
                                    {{ $minutes }} {{ __('min') }}
                                @endif
                                {{ $seconds }} {{ __('sec') }}
                            </span>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('dashboard') }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg">
                            {{ __('Back to Dashboard') }}
                        </a>
                        @if($totalTranslations > 0)
                            <form action="{{ route('review.start') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="practice_all" value="1">
                                <button type="submit" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-bold py-3 px-6 rounded-lg">
                                    {{ __('Practice More (10 words)') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>