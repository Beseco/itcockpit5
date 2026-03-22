{{-- Filter Panel Component --}}
<div x-data="{ open: {{ count(request()->only(['status', 'dhcp', 'has_dns', 'has_comment'])) > 0 ? 'true' : 'false' }} }" class="mb-4">
    <button 
        @click="open = !open" 
        type="button"
        class="flex items-center justify-between w-full px-4 py-2 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
    >
        <span class="font-medium text-gray-700">
            Filters
            @if(count(request()->only(['status', 'dhcp', 'has_dns', 'has_comment'])) > 0)
                <span class="ml-2 px-2 py-1 text-xs bg-indigo-600 text-white rounded-full">
                    {{ count(request()->only(['status', 'dhcp', 'has_dns', 'has_comment'])) }}
                </span>
            @endif
        </span>
        <svg 
            :class="{ 'rotate-180': open }" 
            class="w-5 h-5 transition-transform text-gray-600" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div x-show="open" x-transition class="mt-2 p-4 bg-white border border-gray-200 rounded-md shadow-sm">
        <form method="GET" class="space-y-4">
            {{-- Preserve sort parameters if present --}}
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if(request('direction'))
                <input type="hidden" name="direction" value="{{ request('direction') }}">
            @endif

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select 
                    name="status" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All</option>
                    <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                </select>
            </div>

            {{-- DHCP Range Filter --}}
            <div>
                <label class="flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="dhcp" 
                        value="1" 
                        {{ request('dhcp') ? 'checked' : '' }} 
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    <span class="ml-2 text-sm text-gray-700">In DHCP Range</span>
                </label>
            </div>

            {{-- Has DNS Name Filter --}}
            <div>
                <label class="flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="has_dns" 
                        value="1" 
                        {{ request('has_dns') ? 'checked' : '' }} 
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    <span class="ml-2 text-sm text-gray-700">Has DNS name</span>
                </label>
            </div>

            {{-- Has Comment Filter --}}
            <div>
                <label class="flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="has_comment" 
                        value="1" 
                        {{ request('has_comment') ? 'checked' : '' }} 
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    <span class="ml-2 text-sm text-gray-700">Has comment</span>
                </label>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2 pt-2">
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition-colors"
                >
                    Apply Filters
                </button>
                <a 
                    href="{{ request()->url() }}" 
                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors inline-flex items-center"
                >
                    Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>
