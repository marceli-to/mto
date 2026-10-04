<?php

namespace App\Actions\Invoice;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceState;

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
            'body' => $this->body($invoice),
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

    protected function body(Invoice $invoice): string
    {
        $subject = $invoice->is_reminder
            ? "die {$invoice->reminder_level}. Mahnung zur Rechnung {$invoice->number}"
            : "die Rechnung {$invoice->number}";

        // The contact block below the sign-off is fixed in the mail template,
        // so it is deliberately not part of the editable body.
        return implode("\n\n", [
            'Guten Tag',
            "Im Anhang erhalten Sie {$subject}.",
            'Vielen Dank.',
            'Lieber Gruss',
            'Marcel',
        ]);
    }

    protected function fullName(Contact $contact): string
    {
        return trim("{$contact->firstname} {$contact->name}");
    }
}
