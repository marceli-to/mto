<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;
use RuntimeException;
use Sprain\SwissQrBill as QrBillLib;

/**
 * Builds the Swiss QR bill payment part for an invoice.
 *
 * The account is a plain IBAN rather than a QR-IBAN, so the reference is an
 * ISO 11649 creditor reference (SCOR, "RF…") derived from the invoice number.
 * That needs nothing from the bank and still carries the reference structurally,
 * so a payment can be matched back to its invoice.
 */
class QrBill
{
    /**
     * Additional information is capped by the standard.
     */
    protected const MAX_ADDITIONAL_INFORMATION = 140;

    /**
     * The rendered payment part, or null when the invoice has nothing to pay.
     */
    public function execute(Invoice $invoice): ?string
    {
        if (!$this->isPayable($invoice)) {
            return null;
        }

        $output = new QrBillLib\PaymentPart\Output\HtmlOutput\HtmlOutput($this->build($invoice), 'de');

        return $output->setPrintable(false)->getPaymentPart();
    }

    /**
     * A cancelled invoice is zeroed out and a zero total leaves nothing to
     * collect, so neither gets a payment part.
     */
    public function isPayable(Invoice $invoice): bool
    {
        return !$invoice->isCancelled() && (float) $invoice->grandtotal > 0;
    }

    /**
     * The creditor reference, e.g. 'RF23250597' for invoice 25.0597. Derived
     * rather than stored: invoice numbers are assigned once and never change,
     * so the same invoice always yields the same reference.
     */
    public function reference(Invoice $invoice): string
    {
        return QrBillLib\Reference\RfCreditorReferenceGenerator::generate(
            str_replace('.', '', (string) $invoice->number)
        );
    }

    protected function build(Invoice $invoice): QrBillLib\QrBill
    {
        $invoice->loadMissing('client');

        $qrBill = QrBillLib\QrBill::create();

        $qrBill->setCreditor(
            QrBillLib\DataGroup\Element\StructuredAddress::createWithStreet(
                config('invoice.beneficiary_name'),
                config('invoice.beneficiary_street'),
                config('invoice.beneficiary_building'),
                config('invoice.beneficiary_zip'),
                config('invoice.beneficiary_city'),
                config('invoice.beneficiary_country')
            )
        );

        $qrBill->setCreditorInformation(
            QrBillLib\DataGroup\Element\CreditorInformation::create(
                str_replace(' ', '', (string) config('invoice.iban'))
            )
        );

        $qrBill->setPaymentAmountInformation(
            QrBillLib\DataGroup\Element\PaymentAmountInformation::create(
                config('invoice.currency'),
                round((float) $invoice->grandtotal, 2)
            )
        );

        $qrBill->setPaymentReference(
            QrBillLib\DataGroup\Element\PaymentReference::create(
                QrBillLib\DataGroup\Element\PaymentReference::TYPE_SCOR,
                $this->reference($invoice)
            )
        );

        $qrBill->setAdditionalInformation(
            QrBillLib\DataGroup\Element\AdditionalInformation::create(
                $this->additionalInformation($invoice)
            )
        );

        // A malformed bill that still renders is worse than one that fails
        // loudly here, since it would only surface at the client's bank.
        $violations = $qrBill->getViolations();

        if (count($violations)) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = "{$violation->getPropertyPath()}: {$violation->getMessage()}";
            }

            throw new RuntimeException(
                "Invalid QR bill for invoice {$invoice->number} – " . implode('; ', $messages)
            );
        }

        return $qrBill;
    }

    protected function additionalInformation(Invoice $invoice): string
    {
        $text = $invoice->is_reminder
            ? "{$invoice->reminder_level}. Mahnung zur Rechnung {$invoice->number}"
            : "Rechnung {$invoice->number}";

        if ($invoice->title) {
            $text .= ", {$invoice->title}";
        }

        return mb_substr($text, 0, self::MAX_ADDITIONAL_INFORMATION);
    }
}
