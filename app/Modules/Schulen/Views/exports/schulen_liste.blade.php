<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Schulen – Liste</title>
<style>
  @page { margin: 52pt 28pt 36pt 28pt; }

  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 8px;
    color: #111827;
    margin: 0;
    padding: 0;
  }

  .page-title { font-size: 13px; font-weight: bold; color: #1E1B4B; margin-bottom: 2px; }
  .page-sub   { font-size: 7.5px; color: #6B7280; margin-bottom: 10px; }

  .schule-block {
    page-break-before: always;
    padding-top: 4px;
  }
  .schule-block:first-child { page-break-before: avoid; }

  .schule-name {
    font-size: 12px;
    font-weight: bold;
    color: #1E1B4B;
    margin-bottom: 1px;
  }
  .schule-typ {
    display: inline-block;
    font-size: 7px;
    background: #E0E7FF;
    color: #3730A3;
    padding: 1px 5px;
    border-radius: 3px;
    margin-bottom: 6px;
  }
  .schule-adresse {
    font-size: 8px;
    color: #4B5563;
    margin-bottom: 8px;
  }

  .section-label {
    font-size: 7px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6B7280;
    margin: 8px 0 3px 0;
    border-bottom: 0.5px solid #E5E7EB;
    padding-bottom: 2px;
  }

  table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
  th { background: #F3F4F6; color: #374151; font-size: 7px; font-weight: bold; padding: 2px 4px; text-align: left; }
  td { padding: 2px 4px; border-bottom: 0.5px solid #F3F4F6; font-size: 7.5px; vertical-align: top; }
  tr.even td { background: #F9FAFB; }
  .empty { color: #9CA3AF; }

  .dienst-aktiv     { background: #BBF7D0; color: #14532D; padding: 1px 4px; border-radius: 2px; font-size: 6.5px; }
  .dienst-geplant   { background: #FEF08A; color: #713F12; padding: 1px 4px; border-radius: 2px; font-size: 6.5px; }
  .dienst-nv        { color: #D1D5DB; font-size: 6.5px; }
  .dienst-nichtgew  { background: #FED7AA; color: #7C2D12; padding: 1px 4px; border-radius: 2px; font-size: 6.5px; }
  .dienst-nichtmoe  { background: #FECACA; color: #7F1D1D; padding: 1px 4px; border-radius: 2px; font-size: 6.5px; }
</style>
</head>
<body>

<div class="page-title">Schulen – Detailliste</div>
<div class="page-sub">Stand: {{ $date }} &nbsp;&middot;&nbsp; Exportiert am {{ $datetime }} &nbsp;&middot;&nbsp; {{ $schulen->count() }} Schulen</div>

@php
$statusClass = [
    'aktiv'            => 'dienst-aktiv',
    'geplant'          => 'dienst-geplant',
    'nicht_vorhanden'  => 'dienst-nv',
    'nicht_gewuenscht' => 'dienst-nichtgew',
    'nicht_moeglich'   => 'dienst-nichtmoe',
];
$statusLabel = [
    'aktiv'            => 'Aktiv',
    'geplant'          => 'Geplant',
    'nicht_vorhanden'  => '–',
    'nicht_gewuenscht' => 'Nicht gewünscht',
    'nicht_moeglich'   => 'Nicht möglich',
];
@endphp

@foreach ($schulen as $schule)
  <div class="schule-block">
    <div class="schule-name">{{ $schule->name }}</div>
    <span class="schule-typ">{{ $schule->typLabel() }}</span>
    <div class="schule-adresse">
      {{ $schule->adresse() }}
      @if ($schule->telefon) &nbsp;&middot;&nbsp; Tel: {{ $schule->telefon }} @endif
      @if ($schule->email)   &nbsp;&middot;&nbsp; {{ $schule->email }} @endif
    </div>

    @if ($schule->kontakte->isNotEmpty())
      <div class="section-label">Kontakte</div>
      <table>
        <thead>
          <tr>
            <th style="width:25%">Name</th>
            <th style="width:20%">Rolle</th>
            <th style="width:20%">Telefon</th>
            <th style="width:35%">E-Mail</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($schule->kontakte as $idx => $kontakt)
            <tr class="{{ $idx % 2 === 0 ? '' : 'even' }}">
              <td>{{ $kontakt->vorname }} {{ $kontakt->nachname }}</td>
              <td>{{ $kontakt->rolle ?: '—' }}</td>
              <td>{{ $kontakt->telefon ?: '—' }}</td>
              <td>{{ $kontakt->email ?: '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    @if ($schule->dienstleistungen->isNotEmpty())
      <div class="section-label">Dienstleistungen</div>
      <table>
        <thead>
          <tr>
            <th style="width:50%">Dienstleistung</th>
            <th style="width:15%">Status</th>
            <th style="width:20%">Stunden/Jahr</th>
            <th style="width:15%">VZE</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($schule->dienstleistungen as $idx => $dienst)
            @php
              $status = $dienst->pivot->status ?? 'nicht_vorhanden';
              $cls    = $statusClass[$status] ?? '';
              $label  = $statusLabel[$status] ?? $status;
              $std    = $dienst->pivot->stunden_override ?? $dienst->jahresstunden();
              $vze    = $std !== null ? round($std / \App\Modules\Schulen\Models\Dienstleistung::VZE_JAHRESSTUNDEN, 3) : null;
            @endphp
            <tr class="{{ $idx % 2 === 0 ? '' : 'even' }}">
              <td>{{ $dienst->name }}</td>
              <td><span class="{{ $cls }}">{{ $label }}</span></td>
              <td>{{ $std !== null ? number_format($std, 1, ',', '.') : '—' }}</td>
              <td>{{ $vze !== null ? number_format($vze, 3, ',', '.') : '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    @if ($schule->notizen)
      <div class="section-label">Notizen</div>
      <p style="font-size:7.5px; color:#374151; margin:3px 0;">{{ $schule->notizen }}</p>
    @endif
  </div>
@endforeach

</body>
</html>
