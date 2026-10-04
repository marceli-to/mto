@php
  $isReminder = (bool) $invoice->is_reminder;
  $paragraphs = preg_split("/\n\s*\n/", trim($body));
  $accent = '#e94364';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>{{ $mailSubject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f2; -webkit-font-smoothing:antialiased;">

  {{-- Preheader: the one-line teaser mail clients show next to the subject. --}}
  <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
    {{ $isReminder ? $invoice->reminder_level . '. Mahnung' : 'Rechnung' }} {{ $invoice->number }} &middot; CHF {{ number_format($invoice->grandtotal, 2, '.', "'") }} &middot; PDF im Anhang
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f2;">
    <tr>
      <td align="center" style="padding:32px 16px;">

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px;">

          <!-- Wordmark -->
          <tr>
            <td style="padding:0 8px 24px 8px;">
              <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:22px; font-weight:600; letter-spacing:-0.5px; color:#111111;">marceli.to</span>
              <div style="width:28px; height:3px; background-color:{{ $accent }}; margin-top:6px;"></div>
            </td>
          </tr>

          <!-- Card -->
          <tr>
            <td style="background-color:#ffffff; border-radius:12px; padding:40px;">

              <h1 style="margin:0 0 28px 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:19px; font-weight:600; line-height:26px; color:#111111;">
                @if($isReminder){{ $invoice->reminder_level }}. Mahnung @else Rechnung @endif {{ $invoice->number }}
              </h1>

              @foreach($paragraphs as $paragraph)
                <p style="margin:0 0 16px 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:15px; line-height:24px; color:#333333;">{!! nl2br(e($paragraph)) !!}</p>
              @endforeach

              <!-- Summary -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:32px 0 0 0; background-color:#f9f9f8; border-radius:8px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="padding:4px 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:20px; color:#888888;">Nummer</td>
                        <td align="right" style="padding:4px 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:20px; color:#333333;">{{ $invoice->number }}</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:20px; color:#888888;">Datum</td>
                        <td align="right" style="padding:4px 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:20px; color:#333333;">{{ \Carbon\Carbon::parse($invoice->date)->format('d.m.Y') }}</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:20px; color:#888888;">Zahlbar bis</td>
                        <td align="right" style="padding:4px 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:20px; color:#333333;">{{ \Carbon\Carbon::parse($invoice->date_due)->format('d.m.Y') }}</td>
                      </tr>
                      <tr>
                        <td style="padding:14px 0 0 0; border-top:1px solid #ececea; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; line-height:20px; font-weight:600; color:#111111;">Total</td>
                        <td align="right" style="padding:14px 0 0 0; border-top:1px solid #ececea; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; line-height:20px; font-weight:600; color:#111111;">{{ config('invoice.currency') }} {{ number_format($invoice->grandtotal, 2, '.', "'") }}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Attachment hint -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0 0;">
                <tr>
                  <td style="padding:12px 16px; border-left:3px solid {{ $accent }}; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:20px; color:#666666;">
                    Im Anhang: <span style="color:#333333;">{{ $pdfName }}</span><br>
                    Die Zahlungsangaben finden Sie auf der Rechnung.
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:28px 8px 0 8px; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:12px; line-height:20px; color:#999999;">
              {{ config('invoice.beneficiary_name') }} &middot; {{ config('invoice.beneficiary_street') }} {{ config('invoice.beneficiary_building') }} &middot; {{ config('invoice.beneficiary_zip') }} {{ config('invoice.beneficiary_city') }}<br>
              <a href="mailto:{{ config('mail.from.address') }}" style="color:#999999; text-decoration:underline;">{{ config('mail.from.address') }}</a>
              @if(config('invoice.vat_number'))
                &middot; {{ config('invoice.vat_number') }}
              @endif
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
