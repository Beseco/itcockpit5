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
                    <th style="padding:4px 8px; border:1px solid #e2e8f0;" class="no-print"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($node->zuweisungen as $z)
                @php
                    $canEditZ = auth()->user()->can('base.aufgaben.edit')
                        || ($z->gruppe_id && $z->gruppe?->vorgesetzter_user_id === auth()->id());
                @endphp
                <tr>
                    <td style="padding:4px 8px; border:1px solid #e2e8f0; color:#374151;">{{ $z->gruppe?->name ?? '—' }}</td>
                    <td style="padding:4px 8px; border:1px solid #e2e8f0; color:#374151;">{{ $z->admin?->name ?? '—' }}</td>
                    <td style="padding:4px 8px; border:1px solid #e2e8f0; color:#6b7280;">{{ $z->stellvertreter?->name ?? '—' }}</td>
                    <td style="padding:4px 8px; border:1px solid #e2e8f0; text-align:center;" class="no-print">
                        @if($canEditZ)
                            <button type="button"
                                    onclick="document.getElementById('edit-z-{{ $z->id }}').style.display='table-row'"
                                    style="font-size:0.7rem; color:#6366f1; padding:1px 6px; border:1px solid #c7d2fe; border-radius:3px; background:#eef2ff; cursor:pointer;">
                                Bearbeiten
                            </button>
                        @endif
                    </td>
                </tr>
                @if($canEditZ)
                <tr id="edit-z-{{ $z->id }}" style="display:none; background:#fafafa;">
                    <td style="padding:6px 8px; border:1px solid #e2e8f0; color:#6b7280; font-size:0.75rem;">
                        {{ $z->gruppe?->name ?? '—' }}
                    </td>
                    <td style="padding:6px 8px; border:1px solid #e2e8f0;">
                        <form action="{{ route('aufgaben-zuweisungen.update', $z) }}" method="POST" style="display:contents;">
                            @csrf @method('PATCH')
                            <select name="admin_user_id"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:3px; padding:2px 4px; font-size:0.75rem;">
                                <option value="">— kein Admin —</option>
                                @foreach($allUsers as $u)
                                    <option value="{{ $u->id }}" @selected($u->id == $z->admin_user_id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                    </td>
                    <td style="padding:6px 8px; border:1px solid #e2e8f0;">
                            <select name="stellvertreter_user_id"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:3px; padding:2px 4px; font-size:0.75rem;">
                                <option value="">— kein Stellvertreter —</option>
                                @foreach($allUsers as $u)
                                    <option value="{{ $u->id }}" @selected($u->id == $z->stellvertreter_user_id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                    </td>
                    <td style="padding:6px 8px; border:1px solid #e2e8f0; white-space:nowrap;" class="no-print">
                            <button type="submit"
                                    style="font-size:0.7rem; color:#fff; padding:2px 8px; border:none; border-radius:3px; background:#4f46e5; cursor:pointer; margin-right:4px;">
                                Speichern
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('edit-z-{{ $z->id }}').style.display='none'"
                                    style="font-size:0.7rem; color:#6b7280; padding:2px 6px; border:1px solid #d1d5db; border-radius:3px; background:#fff; cursor:pointer;">
                                Abbrechen
                            </button>
                        </form>
                    </td>
                </tr>
                @endif
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
