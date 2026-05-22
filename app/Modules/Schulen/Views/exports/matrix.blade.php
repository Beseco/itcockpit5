<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Schulen – Dienstleistungsmatrix</title>
<style>
  @page { margin: 52pt 24pt 36pt 24pt; size: A3 landscape; }

  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 7px;
    color: #111827;
    margin: 0;
    padding: 0;
  }

  .page-title { font-size: 13px; font-weight: bold; color: #1E1B4B; margin-bottom: 2px; }
  .page-sub   { font-size: 7px; color: #6B7280; margin-bottom: 8px; }

  table { width: 100%; border-collapse: collapse; table-layout: fixed; }
  th {
    background: #374151; color: #fff;
    font-size: 6.5px; padding: 2px 3px; font-weight: bold;
    text-transform: uppercase; letter-spacing: 0.03em;
    vertical-align: bottom;
    word-wrap: break-word;
  }
  th.dienst-col { text-align: left; background: #F3F4F6; color: #1F2937; width: 120px; }
  th.typ-header { text-align: center; background: #1F2937; color: #fff; font-size: 7px; }

  td { padding: 2px 3px; border-bottom: 1px solid #E5E7EB; font-size: 6.5px; vertical-align: middle; word-wrap: break-word; }
  td.dienst-name { background: #FAFAFA; font-weight: bold; color: #1F2937; width: 120px; }
  tr.even td { background: #F9FAFB; }
  tr.even td.dienst-name { background: #F3F4F6; }

  tr.kat-header td {
    background: #E0E7FF;
    font-weight: bold;
    font-size: 7px;
    color: #1E1B4B;
    padding: 3px 4px;
  }

  .s-aktiv     { background: #BBF7D0; color: #14532D; padding: 1px 3px; border-radius: 2px; font-size: 6px; }
  .s-geplant   { background: #FEF08A; color: #713F12; padding: 1px 3px; border-radius: 2px; font-size: 6px; }
  .s-nv        { color: #D1D5DB; font-size: 6px; }
  .s-nichtgew  { background: #FED7AA; color: #7C2D12; padding: 1px 3px; border-radius: 2px; font-size: 6px; }
  .s-nichtmoe  { background: #FECACA; color: #7F1D1D; padding: 1px 3px; border-radius: 2px; font-size: 6px; }

  .center { text-align: center; }
</style>
</head>
<body>

<div class="page-title">Schulen – Dienstleistungsmatrix</div>
<div class="page-sub">Stand: {{ $date }} &nbsp;&middot;&nbsp; Exportiert am {{ $datetime }}</div>

@php
$statusClass = [
    'aktiv'            => 's-aktiv',
    'geplant'          => 's-geplant',
    'nicht_vorhanden'  => 's-nv',
    'nicht_gewuenscht' => 's-nichtgew',
    'nicht_moeglich'   => 's-nichtmoe',
];
$statusLabel = [
    'aktiv'            => 'Aktiv',
    'geplant'          => 'Geplant',
    'nicht_vorhanden'  => '–',
    'nicht_gewuenscht' => 'N.gew.',
    'nicht_moeglich'   => 'N.mög.',
];
@endphp

<table>
  <thead>
    <tr>
      <th class="dienst-col">Dienstleistung</th>
      @foreach ($schulen as $schule)
        <th class="center" style="width:38px; writing-mode: vertical-rl; transform: rotate(180deg); height: 55px; padding: 4px 2px;">
          {{ $schule->name }}
        </th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach ($kategorien as $kat)
      @php $katDienste = $diensteGruppen->get($kat->id, collect()); @endphp
      @if ($katDienste->isNotEmpty())
        <tr class="kat-header">
          <td colspan="{{ $schulen->count() + 1 }}">{{ $kat->name }}</td>
        </tr>
        @foreach ($katDienste as $dIdx => $dienst)
          <tr class="{{ $dIdx % 2 === 0 ? '' : 'even' }}">
            <td class="dienst-name">{{ $dienst->name }}</td>
            @foreach ($schulen as $schule)
              @php
                $pivot  = $pivots->get($schule->id)?->firstWhere('dienstleistung_id', $dienst->id);
                $status = $pivot?->status ?? 'nicht_vorhanden';
                $cls    = $statusClass[$status] ?? '';
                $label  = $statusLabel[$status] ?? $status;
              @endphp
              <td class="center"><span class="{{ $cls }}">{{ $label }}</span></td>
            @endforeach
          </tr>
        @endforeach
      @endif
    @endforeach
  </tbody>
</table>

</body>
</html>
