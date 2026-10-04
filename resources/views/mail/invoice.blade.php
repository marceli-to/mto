@php
  $paragraphs = preg_split("/\n\s*\n/", trim($body));
  $text = '#333333';
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

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f2;">
    <tr>
      <td align="center" style="padding:32px 16px;">

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px;">

          <!-- Wordmark -->
          <tr>
            <td style="padding:0 8px 24px 8px;">
              <img src="{{ $message->embed(public_path('logo.png')) }}" alt="marceli.to" width="174" style="display:block; width:174px; max-width:174px; height:auto; border:0; outline:none; text-decoration:none;">
            </td>
          </tr>

          <!-- Card -->
          <tr>
            <td style="background-color:#ffffff; border-radius:12px; padding:20px;">

              @foreach($paragraphs as $paragraph)
                <p style="margin:0 0 16px 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:15px; line-height:24px; color:{{ $text }};">{!! nl2br(e($paragraph)) !!}</p>
              @endforeach

              <!-- Contact block, fixed: not part of the editable message -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="padding:8px 0 0 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:21px; color:{{ $text }};">
                    +++<br>
                    marceli.to<br>
                    {{ config('invoice.beneficiary_name') }}<br>
                    <a href="mailto:{{ config('mail.from.address') }}" style="color:{{ $text }}; text-decoration:none;">{{ config('mail.from.address') }}</a><br>
                    {{ config('invoice.phone') }}
                  </td>
                </tr>
                {{-- Temporary notice — delete this row when it no longer applies. --}}
                <tr>
                  <td style="padding:16px 0 0 0; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:21px; color:{{ $text }};">
                    Ich bin neu Montag bis Donnerstag im B&uuml;ro erreichbar.
                  </td>
                </tr>
              </table>

            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
