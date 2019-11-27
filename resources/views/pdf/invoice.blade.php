@include('pdf.partials.header')
<div class="invoice-address">
  <strong>{{$data->client->name}}</strong><br>
  {{$data->client->street}}<br>
  {{$data->client->zip}} {{$data->client->city}}<br>
</div>
<header class="invoice-header cf">
  <h1>Rechnung</h1>
  <table cellspacing="0" cellpadding="0">
    <tr>
      <td>Rechnungs-Nr.:</td>
      <td>{{$data->number}}</td>
    </tr>
    <tr>
      <td>Datum:</td>
      <td>{{ date('d.m.Y', strtotime($data->date))}}</td>
    </tr>
    <tr>
      <td class="bold">Bezahlen bis:</td>
      <td class="bold">{{ date('d.m.Y', strtotime($data->date_due)) }}</td>
    </tr>
  </table>
</header>
<main class="invoice-body">
  <p>{{$data->title}}</p>
  @if ($data->positions)
    <table class="invoice-positions" cellspacing="0" cellpadding="0">
      <thead>
        <tr>
          <th class="position-periode">
            Periode
          </th>
          <th class="position-description">
            Beschreibung
          </th>
          <th class="position-cost">
            Aufwand
          </th>
          <th class="position-amount align-right">
            Betrag CHF
          </th>
        </tr>
      </thead>
      <tbody>
        @foreach($data->positions as $position)
          <tr class="position">
            <td>{{ $position->periode }}</td>
            <td>{{ $position->description }}</td>
            @if ($position->is_flat)
              <td>Pauschal</td>
            @else
              <td>{{ $position->hours }} Std. à {{ $position->rate }}</td>
            @endif
            <td class="align-right">{{ number_format($position->amount, 2, '.', '\'') }}</td>
          </tr>
        @endforeach
        <tr class="position-footer">
          <td>Total</td>
          <td></td>
          <td></td>
          <td class="position-total align-right">{{ number_format($data->total, 2, '.', '\'') }}</td>
        </tr>
      </tbody>
    </table>
    <div class="invoice-vat-info">Nicht MwSt.-Pflichtig</div>
  @endif
</main>
@if (isset($has_journal))
  <style>
    .page-break {
      page-break-after: always;
    }
  </style>
  <div class="page-break"></div>
  <div class="invoice-journal">
    <h2>Journal</h2>
    <table class="invoice-positions" cellspacing="0" cellpadding="0">
      <thead>
        <tr>
          <th class="position-periode">
            Periode
          </th>
          <th class="position-description">
            Beschreibung
          </th>
          <th class="position-cost">
            Aufwand
          </th>
          <th class="position-amount align-right">
            Betrag CHF
          </th>
        </tr>
      </thead>
      <tbody>
        <tr class="position">
          <td>September 2019</td>
          <td>Update Network (USA)</td>
          <td>5.25 Std. à 125.00</td>
          <td class="align-right">656.25</td>
        </tr>
        <tr class="position-footer">
          <td>Total</td>
          <td></td>
          <td></td>
          <td class="position-total  align-right">7500.00</td>
        </tr>
      </tbody>
    </table>
  </div>
@endif
@include('pdf.partials.footer')