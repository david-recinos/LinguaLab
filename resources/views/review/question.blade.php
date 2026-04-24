<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Review Session') }}
            </h2>
            <form action="{{ route('review.end') }}" method="POST">
                @csrf
                <button type="submit" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 text-sm">
                    {{ __('End Session') }}
                </button>
            </form>
        </div>
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
                            {{ $question['progress']['current'] }} / {{ $question['progress']['total'] }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: {{ ($question['progress']['current'] / $question['progress']['total']) * 100 }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Question Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <!-- Direction Indicator -->
                    <div class="flex items-center justify-center gap-2 mb-6 text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ $question['question_language'] }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $question['answer_language'] }}</span>
                    </div>

                    <!-- Question -->
                    <div class="text-center mb-8">
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $question['question'] }}
                        </h3>
                        @if($translation->type === 'word' && $translation->wordType)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                {{ $translation->wordType->name }}
                            </p>
                        @endif
                    </div>

                    @if($question['input_method']->value === 'typing')
                        <!-- Typing Input -->
                        <form action="{{ route('review.submit') }}" method="POST" id="reviewForm">
                            @csrf
                            <input type="hidden" name="time_spent" id="timeSpent" value="0">
                            
                            <div class="mb-6">
                                <label for="answer" class="sr-only">{{ __('Your Answer') }}</label>
                                <input 
                                    type="text" 
                                    name="answer" 
                                    id="answer"
                                    class="w-full text-center text-2xl p-4 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="{{ __('Type your answer...') }}"
                                    autocomplete="off"
                                    autofocus
                                    required
                                >
                            </div>

                            <div class="flex gap-4 justify-center">
                                <button type="button" onclick="document.getElementById('reviewForm').submit()" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg text-lg">
                                    {{ __('Submit') }}
                                </button>
                                <button type="button" onclick="skipQuestion()" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-bold py-3 px-6 rounded-lg">
                                    {{ __('Skip') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <!-- Multiple Choice Input -->
                        <form action="{{ route('review.submit') }}" method="POST" id="reviewForm">
                            @csrf
                            <input type="hidden" name="time_spent" id="timeSpent" value="0">

                            <!-- AI Powered Badge -->
                            @if($distractorsSource === 'ai')
                                <div class="flex items-center justify-center gap-1 mb-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" />
                                        </svg>
                                        {{ __('AI Powered') }}
                                    </span>
                                </div>
                            @elseif($distractorsSource === 'fallback')
                                <div class="flex items-center justify-center gap-2 mb-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ __('Basic Options') }}
                                    </span>
                                    <form action="{{ route('review.retry-distractors') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 underline">
                                            {{ __('Try AI') }}
                                        </button>
                                    </form>
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-4 mb-6" id="optionsGrid">
                                @foreach($multipleChoiceOptions as $index => $option)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="answer" value="{{ $option }}" class="sr-only peer">
                                        <div class="p-4 rounded-lg border-2 border-gray-200 dark:border-gray-600 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 hover:border-gray-300 dark:hover:border-gray-500 transition-colors text-center">
                                            <span class="text-lg text-gray-900 dark:text-gray-100">{{ $option }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <div id="selectionError" class="hidden text-red-500 dark:text-red-400 text-center mb-4">
                                {{ __('Please select an answer.') }}
                            </div>

                            <div class="flex gap-4 justify-center">
                                <button type="button" id="submitBtn" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg text-lg">
                                    {{ __('Submit') }}
                                </button>
                                <button type="button" onclick="skipQuestion()" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-bold py-3 px-6 rounded-lg">
                                    {{ __('Skip') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        // Timer tracking
        let startTime = Date.now();
        
        // Update time spent before form submit
        document.getElementById('reviewForm')?.addEventListener('submit', function() {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('timeSpent').value = elapsed;
        });

        // Multiple choice submit handler
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                const selected = document.querySelector('input[name="answer"]:checked');
                const errorEl = document.getElementById('selectionError');
                
                if (!selected) {
                    errorEl.classList.remove('hidden');
                    return;
                }
                
                errorEl.classList.add('hidden');
                document.getElementById('reviewForm').submit();
            });
        }

        // Hide error on option selection
        document.querySelectorAll('input[name="answer"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.getElementById('selectionError').classList.add('hidden');
            });
        });

        // Skip function
        function skipQuestion() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('review.skip') }}';
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            
            document.body.appendChild(form);
            form.submit();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Enter to submit
            if (e.key === 'Enter' && document.activeElement.id === 'answer') {
                e.preventDefault();
                document.getElementById('reviewForm').submit();
            }
        });
    </script>
    @endpush
</x-app-layout>