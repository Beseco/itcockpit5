@props(['viewPath'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        @include($viewPath)
    </div>
</div>
