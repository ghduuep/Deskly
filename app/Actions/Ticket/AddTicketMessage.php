<?php

namespace App\Actions\Ticket;

use App\Events\Message\MessageSent;
use App\HtmlSanitizer;
use App\MessageDirection;
use App\MessageType;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Ticket;
use App\TicketStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

final class AddTicketMessage
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function handle(
        Ticket $ticket,
        Contact $author,
        string $content,
        MessageDirection $direction,
    ): Message {
        return DB::transaction(function () use (
            $ticket,
            $author,
            $content,
            $direction,
        ) {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if ($ticket->status === TicketStatus::Closed) {
                throw new DomainException(
                    "Cannot add messages to a closed ticket.",
                );
            }

            if ($ticket->status === TicketStatus::Resolved) {
                if ($direction === MessageDirection::Outbound) {
                    throw new DomainException(
                        "Reopen the ticket before replying.",
                    );
                }
                $ticket->reopen();
            }

            if (
                $direction === MessageDirection::Inbound &&
                $ticket->status === TicketStatus::Pending
            ) {
                $ticket->resume();
            }

            if (
                $direction === MessageDirection::Outbound &&
                $ticket->status === TicketStatus::Open
            ) {
                $ticket->pend();
            }

            $message = $ticket->messages()->create([
                "contact_id" => $author->id,
                "content" => $this->sanitizer->sanitize($content),
                "type" => MessageType::Reply,
                "direction" => $direction,
            ]);

            MessageSent::dispatch($message->id, $ticket->id);

            return $message;
        });
    }
}
