<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Schulen – Dienstleistungen</title>
<style>
  @page { margin: 52pt 28pt 36pt 28pt; }

  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 8.5px;
    color: #111827;
    margin: 0;
    padding: 0;
  }

  .page-title { font-size: 13px; font-weight: bold; color: #1E1B4B; margin-bottom: 2px; }
  .page-sub   { font-size: 7.5px; color: #6B7280; margin-bottom: 10px; }

  .kat-heading {
    background: #E0E7FF;
    color: #1E1B4B;
    font-size: 9px;
    font-weight: bold;
    padding: 4px 6px;
    margin: 14px 0 4px 0;
    border-left: 3px solid #6366F1;
  }

  table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  td { padding: 3px 6px; border-bottom: 1px solid #E5E7EB; vertical-align: top; font-size: 8px; }
  td.label-col { background: #F9FAFB; font-weight: bold; color: #1F2937; width: 30%; }
  td.val-col   { color: #374151; }
  .meta { font-size: 7px; color: #6B7280; margin-top: 2px; }
  .doku { font-size: 7px; color: #4F46E5; }
  .empty { color: #9CA3AF; }
</style>
</head>
<body>

<div class="page-title">Schulen – Dienstleistungen</div>
<div class="page-sub">Stand: {{ $date }} &nbsp;&middot;&nbsp; Exportiert am {{ $datetime }} &nbsp;&middot;&nbsp; {{ $dienste->count() }} Dienste gesamt</div>

@foreach ($kategorien as $kat)
  @php $katDienste = $diensteGruppen->get($kat->id, collect()); @endphp
  @if ($katDienste->isNotEmpty())
    <div class="kat-heading">{{ $kat->name }}</div>
    <table>
      <tbody>
        @foreach ($katDienste as $dienst)
          <tr>
            <td class="label-col">{{ $dienst->name }}</td>
            <td class="val-col">
              @if ($dienst->beschreibung)
                {{ $dienst->beschreibung }}
              @else
                <span class="empty">Keine Beschreibung</span>
              @endif
              @php
                $stunden = $dienst->jahresstunden();
                $vze     = $dienst->vzeProSchule();
              @endphp
              @if ($stunden !== null)
                <div class="meta">
                  {{ number_format($stunden, 1, ',', '.') }} Std./Jahr
                  @if ($vze !== null)
                    &nbsp;&middot;&nbsp; {{ number_format($vze, 3, ',', '.') }} VZE/Schule
                  @endif
                </div>
              @endif
              @if ($dienst->dokumentation_url)
                <div class="doku">Doku: {{ $dienst->dokumentation_url }}</div>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
@endforeach

</body>
</html>
