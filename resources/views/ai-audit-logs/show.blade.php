<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('AI Audit Log Details') }}
            </h2>
            <a href="{{ route('ai-audit-logs.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                {{ __('Back to Logs') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Request Information</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">#{{ $aiAuditLog->id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Timestamp</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $aiAuditLog->created_at->format('Y-m-d H:i:s') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $aiAuditLog->user?->name ?? 'System' }} ({{ $aiAuditLog->user?->email ?? 'N/A' }})</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Provider</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $aiAuditLog->provider }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Model</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $aiAuditLog->model ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Feature</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $aiAuditLog->feature }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Translation ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $aiAuditLog->translation_id ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold mb-4">Response Information</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="mt-1">
                                        @if($aiAuditLog->success)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Success</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">Failed</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Response Time</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $aiAuditLog->response_time_ms ? $aiAuditLog->response_time_ms . 'ms' : 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tokens Used</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $aiAuditLog->tokens_used ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Error Message</dt>
                                    <dd class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $aiAuditLog->error_message ?? 'None' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Prompt -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Prompt</h3>
                        <div class="bg-gray-100 dark:bg-gray-900 rounded p-4 overflow-x-auto">
                            <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $aiAuditLog->prompt ?? 'N/A' }}</pre>
                        </div>
                    </div>

                    <!-- Response -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Raw Response</h3>
                        <div class="bg-gray-100 dark:bg-gray-900 rounded p-4 overflow-x-auto max-h-96">
                            <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $aiAuditLog->response ?? 'N/A' }}</pre>
                        </div>
                    </div>

                    <!-- Parsed Result -->
                    @if($aiAuditLog->parsed_result)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold mb-2">Parsed Result</h3>
                            <div class="bg-gray-100 dark:bg-gray-900 rounded p-4 overflow-x-auto">
                                <pre class="text-sm text-gray-800 dark:text-gray-200">{{ json_encode($aiAuditLog->parsed_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
