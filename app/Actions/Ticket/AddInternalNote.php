<?php

namespace App\Actions\Ticket;

use App\Events\Message\InternalNoteAdded;
use App\HtmlSanitizer;
use App\MessageType;
use App\Models\Contact;
use App\Models\Ticket;
use App\TicketStatus;
use Illuminate\Support\Facades\DB;

final class AddInternalNote
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function handle(Ticket $ticket, Contact $author, string $content)
    {
        return DB::transaction(function () use ($ticket, $author, $content) {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if ($ticket->status === TicketStatus::Closed) {
                throw new DomainException(
                    "Cannot add internal notes to a closed ticket.",
                );
            }

            $message = $ticket->messages()->create([
                "contact_id" => $author->id,
                "content" => $this->sanitizer->sanitize($content),
                "type" => MessageType::Internal,
                "direction" => null,
            ]);

            InternalNoteAdded::dispatch($message->id, $ticket->id);

            return $message;
        });
    }
}
