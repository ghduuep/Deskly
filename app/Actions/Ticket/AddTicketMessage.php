<?php

namespace App\Actions\Ticket;

use App\HtmlSanitizer;
use App\MessageDirection;
use App\MessageType;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Draft;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

final class AddTicketMessage
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function handle(
        Ticket $ticket,
        Contact $author,
        string $content,
        MessageDirection $direction,
        Channel $channel,
        ?Draft $draft = null,
    ): Message {
        // Prepare externally supplied content.
        $content = $this->sanitizer->sanitize($content);

        return DB::transaction(function () use (
            $ticket,
            $author,
            $content,
            $direction,
            $channel,
            $draft,
        ) {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            $message = $ticket->addReply(
                $author,
                $content,
                $direction,
                $channel,
            );

            if ($draft !== null) {
                Draft::query()
                    ->whereKey($draft->id)
                    ->where("ticket_id", $ticket->id)
                    ->where("user_id", $author->user_id)
                    ->where("type", MessageType::Reply->value)
                    ->delete();
            }

            return $message;
        });
    }
}
