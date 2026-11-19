{{--
  Page size and margins, plus the logo at the top and the address line at the
  bottom of every page. Both are running elements: they leave the flow and
  print in the page's margin boxes. Include it first thing in <body>, so the
  first page has them too.

  $margin       CSS margin of the page (default 30mm 20mm)
  $pageNumbers  print the page number at the bottom right
--}}
<style>
  @page {
    size: A4;
    margin: {{ $margin ?? '30mm 20mm' }};

    @top-left {
      content: element(page-header);
      vertical-align: top;
    }

    @bottom-left {
      content: element(page-footer);
      vertical-align: bottom;
    }

    @if ($pageNumbers ?? false)
    @bottom-right {
      content: counter(page);
      vertical-align: bottom;
      padding-bottom: 10mm;
      font-family: 'Poppins', sans-serif;
      font-weight: 300;
      font-size: 8pt;
      line-height: 1;
    }
    @endif
  }

  /* 10mm from the sheet edge, where the invoice has its logo and footer too */
  .page-frame-header {
    position: running(page-header);
    padding-top: 10mm;
  }

  .page-frame-footer {
    position: running(page-footer);
    padding-bottom: 10mm;
    font-family: 'Poppins', sans-serif;
    font-weight: 300;
    font-size: 8pt;
    line-height: 1;
    white-space: nowrap;
  }
</style>
<div class="page-frame-header">@include('pdf.partials.logo')</div>
<div class="page-frame-footer">@include('pdf.partials.footer-address')</div>
