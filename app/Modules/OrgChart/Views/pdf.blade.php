<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; margin: 0; padding: 10px; }
        h1 { font-size: 14px; margin-bottom: 4px; }
        .meta { font-size: 8px; color: #6b7280; margin-bottom: 12px; }
        .kpi-row { display: flex; gap: 10px; margin-bottom: 12px; }
        .kpi { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 6px 10px; text-align: center; flex: 1; }
        .kpi-value { font-size: 16px; font-weight: bold; color: #1f2937; }
        .kpi-label { font-size: 7px; color: #6b7280; }
        .frames { display: flex; gap: 8px; flex-wrap: wrap; }
        .frame { border: 2px solid #d97706; border-radius: 6px; min-width: 140px; max-width: 200px; margin-bottom: 8px; }
        .frame-header { background: #fef3c7; padding: 4px 8px; font-weight: bold; font-size: 9px; border-bottom: 1px solid #d97706; }
        .group-card { border: 1px solid #e5e7eb; border-radius: 4px; margin: 4px; overflow: hidden; }
        .group-header { padding: 3px 6px; font-weight: bold; font-size: 8px; background: #f3f4f6; }
        .task-item { padding: 2px 6px; font-size: 8px; color: #374151; border-top: 1px solid #f3f4f6; }
        .iface-tag { display: inline-block; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 3px; padding: 1px 4px; font-size: 7px; margin: 2px; }
        .section-title { font-size: 10px; font-weight: bold; color: #374151; margin: 12px 0 4px; border-bottom: 1px solid #e5e7eb; padding-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; font-size: 8px; }
        th { background: #f9fafb; padding: 3px 6px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        td { padding: 3px 6px; border-bottom: 1px solid #f3f4f6; }
    </style>
</head>
<body>
    <h1>Organigramm: {{ $version->name }}</h1>
    <div class="meta">
        Status: {{ \App\Modules\OrgChart\Models\OrgVersion::STATUS_LABELS[$version->status] }} &nbsp;|&nbsp;
        Erstellt von: {{ $version->created_by }} &nbsp;|&nbsp;
        Stand: {{ $version->updated_at->format('d.m.Y') }}
    </div>

    @php
        $totalFte   = (float) $allNodes->sum('headcount');
        $groupCount = $allNodes->whereIn('type', ['frame', 'group'])->count();
        $taskCount  = $allNodes->where('type', 'task')->count();
        $topNodes   = $rootNodes->whereIn('type', ['top', 'staff'])->sortBy('sort_order');
        $frameNodes = $rootNodes->where('type', 'frame')->sortBy('sort_order');
    @endphp

    <div class="kpi-row">
        <div class="kpi"><div class="kpi-value">{{ $groupCount }}</div><div class="kpi-label">Gruppen</div></div>
        <div class="kpi"><div class="kpi-value">{{ number_format($totalFte, 1, ',', '') }}</div><div class="kpi-label">FTE gesamt</div></div>
        <div class="kpi"><div class="kpi-value">{{ $taskCount }}</div><div class="kpi-label">Aufgaben</div></div>
        <div class="kpi"><div class="kpi-value">{{ $interfaces->count() }}</div><div class="kpi-label">Schnittstellen</div></div>
    </div>

    <div class="frames">
        @foreach($frameNodes as $frame)
        @php
            $frameChildren = $allNodes->where('parent_id', $frame->id)->where('type', 'group')->sortBy('sort_order');
        @endphp
        <div class="frame">
            <div class="frame-header">{{ $frame->name }}</div>
            @foreach($frameChildren as $group)
            @php
                $tasks = $allNodes->where('parent_id', $group->id)->where('type', 'task')->sortBy('sort_order');
                $gIfaces = $interfacesByNode->get($group->id, collect());
            @endphp
            <div class="group-card">
                <div class="group-header" style="border-left: 3px solid {{ $group->color ?? '#6b7280' }}; padding-left: 4px;">
                    {{ $group->name }}
                    @if($group->headcount) &nbsp;({{ number_format($group->headcount, 1, ',', '') }} FTE) @endif
                </div>
                @foreach($tasks as $task)
                <div class="task-item">● {{ $task->name }}</div>
                @endforeach
                @if($gIfaces->isNotEmpty())
                <div style="padding: 3px 4px;">
                    @foreach($gIfaces as $iface)
                    <span class="iface-tag">↔ {{ $iface->toNode->name }}: {{ $iface->label }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    @if($interfaces->isNotEmpty())
    <div class="section-title">Schnittstellen-Matrix</div>
    <table>
        <thead><tr><th>Von</th><th>Zu</th><th>Thema</th><th>Beschreibung</th></tr></thead>
        <tbody>
            @foreach($interfaces as $iface)
            <tr>
                <td>{{ $iface->fromNode->name }}</td>
                <td>{{ $iface->toNode->name }}</td>
                <td>{{ $iface->label }}</td>
                <td>{{ $iface->description ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</body>
</html>
