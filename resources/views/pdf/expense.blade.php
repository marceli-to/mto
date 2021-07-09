@include('pdf.partials.header')
<style>
.payment-info-box {
  display: none !important;
}
</style>
<header class="invoice-header cf">
  <h1>Ausgabe</h1>
</header>
<main class="invoice-body">
  <div>
    <strong>{{$data->title}}</strong>
  </div>
  <table class="invoice-positions" cellspacing="0" cellpadding="0" style="margin-top: 2mm; border-top: .1mm solid #000000;">
    <tbody>
      <tr class="position" style="">
        <td>{{$data->description}}, {{ date('d.m.Y', strtotime($data->date))}}</td>
        <td class="align-right">{{ number_format($data->amount, 2, '.', '\'') }}</td>
      </tr>
      <tr class="position-footer position-footer--grandtotal">
        <td>Total {{$data->currency}}</td>
        <td class="position-total align-right">{{ number_format($data->amount, 2, '.', '\'') }}</td>
      </tr>
    </tbody>
  </table>
  <div>
  </div>
</main>
<style>
  .page-break {
    page-break-after: always;
  }
</style>
<div class="page-break"></div>
<div style="margin-top: 35mm; max-height: 220mm; max-width: 160mm; overflow: hidden; margin-left: auto; margin-right: auto">
<img src="{{ asset('storage/media/expenses/' . $data->number . '.jpg') }}" width="100" style="width: auto; max-width: 100%; height: auto; display: block;">
</div>
@include('pdf.partials.footer')