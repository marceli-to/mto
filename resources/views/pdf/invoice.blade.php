@include('pdf.partials.header')
<div class="invoice-address">
  <strong>Atelier Strut AG</strong><br>
  Neuwiesenstrasse 69<br>
  8400 Winterthur<br>
</div>
<header class="invoice-header cf">
  <h1>Rechnung</h1>
  <table cellspacing="0" cellpadding="0">
    <tr>
      <td>Rechnungs-Nr.:</td>
      <td>19.0001</td>
    </tr>
    <tr>
      <td>Datum:</td>
      <td>24.11.2019</td>
    </tr>
    <tr>
      <td class="bold">Bezahlen bis:</td>
      <td class="bold">04.12.2019</td>
    </tr>
  </table>
</header>
<main class="invoice-body">
  <p>Programmierung Webseite «www.strut.ch»</p>
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
</main>
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
@include('pdf.partials.footer')