{{-- Search Bar Component --}}
<div class="mb-4">
    <form action="{{ route('network.search') }}" method="GET" class="flex gap-2">
        <div class="flex-1 relative">
            <input 
                type="text" 
                name="q" 
                value="{{ request('q') }}" 
                placeholder="Search VLANs and IP addresses..." 
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                minlength="3"
                required
            >
            <svg class="absolute right-3 top-3 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition-colors">
            Search
        </button>
    </form>
    @if(request('q') && strlen(request('q')) < 3)
        <p class="mt-2 text-sm text-red-600">Please enter at least 3 characters</p>
    @endif
</div>
