@props(['perPage' => 25, 'options' => [25, 50, 100, 250]])

<div class="flex items-center gap-1.5 text-sm text-gray-500">
    <span class="text-xs whitespace-nowrap">Einträge pro Seite:</span>
    <select
        onchange="(function(v){var u=new URL(window.location);u.searchParams.set('per_page',v);u.searchParams.delete('page');window.location.href=u.toString();})(this.value)"
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs py-1">
        @foreach($options as $opt)
            <option value="{{ $opt }}" {{ (int)$perPage === $opt ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
    </select>
</div>
