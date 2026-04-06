@php
    $svgPaths = [
        'server'   => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
        'firewall' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'router'   => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0',
        'switch'   => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'cloud'    => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
    ];
    $colorMap = [
        'blue'   => 'text-blue-600',
        'red'    => 'text-red-600',
        'green'  => 'text-green-600',
        'yellow' => 'text-yellow-600',
        'purple' => 'text-purple-600',
    ];
    $path  = $svgPaths[$symbol ?? 'server'] ?? $svgPaths['server'];
    $color = $colorMap[$color ?? 'blue'] ?? 'text-blue-600';
    $size  = $size ?? 'w-5 h-5';
@endphp
<svg class="{{ $size }} {{ $color }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ $title ?? '' }}">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"/>
</svg>
