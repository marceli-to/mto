<?php

namespace App\Actions\Invoice;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceState;
use Carbon\Carbon;

/**
 * Everything the send form needs to open pre-filled: the client's contacts to
 * pick a recipient from, plus a subject and message the user can edit or send
 * as they are.
 */
class MailDefaults
{
    public function execute(Invoice $invoice)
    {
        $invoice->load(['client.contacts']);

        $contacts = $invoice->client->contacts->filter(fn ($contact) => filled($contact->email))->values();
        $recipient = $contacts->first();

        return response()->json([
            'contacts' => $contacts->map(fn ($contact) => [
                'email' => $contact->email,
                'name' => $this->fullName($contact),
            ]),
            'to' => $recipient?->email ?? '',
            'cc' => '',
            'subject' => $this->subject($invoice),
            'body' => $this->body($invoice, $recipient),
            'attachment' => (new Pdf)->filename($invoice),
            // So the form can say who gets a copy instead of guessing.
            'copy_to' => config('mail.bcc.address'),
            'marks_pending' => $invoice->state_id === InvoiceState::DRAFT,
        ]);
    }

    protected function subject(Invoice $invoice): string
    {
        $label = $invoice->is_reminder
            ? "{$invoice->reminder_level}. Mahnung zur Rechnung {$invoice->number}"
            : "Rechnung {$invoice->number}";

        return $invoice->title ? "{$label} – {$invoice->title}" : $label;
    }

    protected function body(Invoice $invoice, ?Contact $recipient): string
    {
        $greeting = 'Guten Tag' . ($recipient ? ' ' . $this->fullName($recipient) : '');
        $amount = config('invoice.currency') . ' ' . number_format($invoice->grandtotal, 2, '.', "'");
        $due = Carbon::parse($invoice->date_due)->format('d.m.Y');
        $sender = config('invoice.beneficiary_name');

        if ($invoice->is_reminder) {
            return implode("\n\n", [
                $greeting,
                "Die Rechnung {$invoice->number} über {$amount} vom "
                    . Carbon::parse($invoice->date)->format('d.m.Y')
                    . " ist noch offen. Im Anhang erhalten Sie die {$invoice->reminder_level}. Mahnung.",
                "Ich bitte Sie, den Betrag bis {$due} zu überweisen. Falls Sie die Zahlung bereits ausgelöst haben, betrachten Sie dieses Schreiben als gegenstandslos.",
                "Freundliche Grüsse\n{$sender}",
            ]);
        }

        $subject = $invoice->title
            ? "die Rechnung {$invoice->number} über {$amount} für {$invoice->title}"
            : "die Rechnung {$invoice->number} über {$amount}";

        return implode("\n\n", [
            $greeting,
            "Im Anhang erhalten Sie {$subject}. Der Betrag ist zahlbar bis {$due}.",
            'Besten Dank für die gute Zusammenarbeit.',
            "Freundliche Grüsse\n{$sender}",
        ]);
    }

    protected function fullName(Contact $contact): string
    {
        return trim("{$contact->firstname} {$contact->name}");
    }
}
