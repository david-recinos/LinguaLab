<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Review Session') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <!-- Progress Bar -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Progress') }}
                        </span>
                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            {{ $result['progress']['current'] }} / {{ $result['progress']['total'] }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: {{ ($result['progress']['current'] / $result['progress']['total']) * 100 }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Feedback Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg {{ $result['is_correct'] ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500' }}">
                <div class="p-8">
                    <!-- Result Indicator -->
                    <div class="text-center mb-6">
                        @if($result['is_correct'])
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-green-600 dark:text-green-400">{{ __('Correct!') }}</h3>
                        @else
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-red-600 dark:text-red-400">{{ __('Incorrect') }}</h3>
                        @endif
                    </div>

                    <!-- Translation Details -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('Question') }}</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $result['translation']->source_text }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('Your Answer') }}</p>
                                <p class="text-lg {{ $result['is_correct'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $result['user_answer'] }}
                                </p>
                            </div>
                        </div>

                        @if(! $result['is_correct'])
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('Correct Answer') }}</p>
                                <p class="text-xl font-bold text-green-600 dark:text-green-400">
                                    {{ $result['correct_answer'] }}
                                </p>
                            </div>
                        @endif

                        @if($result['translation']->example_sentence)
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('Example') }}</p>
                                <p class="text-gray-700 dark:text-gray-300 italic">
                                    {{ $result['translation']->example_sentence }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-center">
                        <form action="{{ route('review.next') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg text-lg">
                                {{ __('Next') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block ml-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-advance after short delay for correct answers (optional)
        // Uncomment if you want this behavior:
        // @if($result['is_correct'])
        // setTimeout(() => {
        //     document.querySelector('form').submit();
        // }, 1500);
        // @endif

        // Keyboard shortcut: Space or Enter to continue
        document.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('form').submit();
            }
        });
    </script>
    @endpush
</x-app-layout>