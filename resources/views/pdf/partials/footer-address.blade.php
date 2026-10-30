{{-- The address line at the bottom of every PDF --}}
{!! collect([
  config('invoice.beneficiary_name'),
  config('invoice.beneficiary_street') . ' ' . config('invoice.beneficiary_building'),
  config('invoice.beneficiary_zip') . ' ' . config('invoice.beneficiary_city'),
  '078 749 74 09',
  'm@marceli.to',
])->map(fn ($item) => e($item))->implode('&thinsp;&thinsp;•&thinsp;&thinsp;') !!}
