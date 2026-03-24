@php $indent = $depth * 20; @endphp

<div class="aufgabe-block" style="margin-left: {{ $indent }}px; margin-bottom: 1.25rem;">

    {{-- Titelzeile --}}
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:0.5rem;">
        @if($depth === 0)
            <h3 style="font-size:1.1rem; font-weight:700; color:#1e293b; margin:0;">{{ $node->name }}</h3>
        @elseif($depth === 1)
            <h4 style="font-size:1rem; font-weight:600; color:#334155; margin:0;">
                <a href="{{ route('aufgaben.show', $node) }}" style="color:#334155; text-decoration:none;" class="no-print-link">{{ $node->name }}</a>
            </h4>
        @else
            <h5 style="font-size:0.9rem; font-weight:600; color:#475569; margin:0;">
                <a href="{{ route('aufgaben.show', $node) }}" style="color:#475569; text-decoration:none;" class="no-print-link">{{ $node->name }}</a>
            </h5>
        @endif
        @can('base.aufgaben.edit')
            @if($depth > 0)
                <a href="{{ route('aufgaben.edit', $node) }}"
                   class="no-print"
                   style="font-size:0.7rem; color:#6366f1; text-decoration:none; padding:1px 6px; border:1px solid #c7d2fe; border-radius:4px; background:#eef2ff; white-space:nowrap;">
                    Bearbeiten
                </a>
            @endif
        @endcan
    </div>

    {{-- Beschreibung --}}
    @if($node->beschreibung)
        <div class="md-render"
             data-md="{{ e($node->beschreibung) }}"
             style="font-size:0.875rem; color:#374151; line-height:1.6; margin-bottom:0.75rem; padding:0.75rem; background:#f8fafc; border-left:3px solid #c7d2fe; border-radius:0 4px 4px 0;">
        </div>
    @endif

    {{-- Zuweisungen --}}
    @if($node->zuweisungen->isNotEmpty())
        <table style="width:100%; border-collapse:collapse; font-size:0.8rem; margin-bottom:0.5rem;">
            <thead>
                <tr style="background:#f1f5f9;">
                    <th style="padding:4px 8px; text-align:left; color:#64748b; font-weight:600; border:1px solid #e2e8f0;">Gruppe</th>
                    <th style="padding:4px 8px; text-align:left; color:#64748b; font-weight:600; border:1px solid #e2e8f0;">Admin</th>
                    <th style="padding:4px 8px; text-align:left; color:#64748b; font-weight:600; border:1px solid #e2e8f0;">Stellvertreter</th>
                </tr>
            </thead>
            <tbody>
                @foreach($node->zuweisungen as $z)
                <tr>
                    <td style="padding:4px 8px; border:1px solid #e2e8f0; color:#374151;">{{ $z->gruppe?->name ?? '—' }}</td>
                    <td style="padding:4px 8px; border:1px solid #e2e8f0; color:#374151;">{{ $z->admin?->name ?? '—' }}</td>
                    <td style="padding:4px 8px; border:1px solid #e2e8f0; color:#6b7280;">{{ $z->stellvertreter?->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Trennlinie nur zwischen Hauptebenen --}}
    @if($depth === 0 && $node->children->isNotEmpty())
        <hr style="border:none; border-top:1px solid #e2e8f0; margin:0.75rem 0;">
    @endif

    {{-- Rekursive Kinder --}}
    @foreach($node->children as $child)
        @include('aufgaben._show_node', ['node' => $child, 'depth' => $depth + 1])
    @endforeach

</div>
