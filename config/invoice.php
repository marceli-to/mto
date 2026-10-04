<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Bank account
  |--------------------------------------------------------------------------
  |
  | The account invoices are paid into. Single source for the payment details
  | printed on the invoice and encoded in the QR bill, so the two cannot drift
  | apart.
  |
  */

  'iban'      => 'CH22 8080 8003 1865 2284 6',
  'bank_name' => 'Raiffeisenbank Weinland',

  /*
  |--------------------------------------------------------------------------
  | Beneficiary
  |--------------------------------------------------------------------------
  |
  | Kept in separate fields because the Swiss QR bill standard no longer
  | accepts combined addresses. Must match the account holder.
  |
  */

  'beneficiary_name'     => 'Marcel Stadelmann',
  'beneficiary_street'   => 'Letzigraben',
  'beneficiary_building' => '149',
  'beneficiary_zip'      => '8047',
  'beneficiary_city'     => 'Zürich',
  'beneficiary_country'  => 'CH',

  /*
  |--------------------------------------------------------------------------
  | VAT number
  |--------------------------------------------------------------------------
  |
  | Shown on invoices and in the footer of invoice mails
  |
  */

  'vat_number' => 'CHE-398.845.092 MWST',

  /*
  |--------------------------------------------------------------------------
  | Currency
  |--------------------------------------------------------------------------
  |
  | Used in generated invoices
  |
  */

  'currency' => 'CHF',

];
