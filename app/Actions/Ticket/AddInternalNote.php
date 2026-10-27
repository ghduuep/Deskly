<?php

namespace App\Actions\Ticket;

use App\Enum\MessageType;
use App\HtmlSanitizer;
use App\Models\Contact;
use App\Models\Draft;
use App\Models\Message;
use App\Models\Ticket;
use DomainException;
use Illuminate\Support\Facades\DB;

final class AddInternalNote
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function handle(
        Ticket $ticket,
        Contact $author,
        string $content,
        ?Draft $draft = null,
    ): Message {
        $content = $this->sanitizer->sanitize($content);

        return DB::transaction(function () use (
            $ticket,
            $author,
            $content,
            $draft,
        ): Message {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if ($draft !== null) {
                if ($author->user_id === null) {
                    throw new DomainException(
                        "A draft requires an authenticated owner.",
                    );
                }

                $draft = Draft::query()
                    ->lockForUpdate()
                    ->findOrFail($draft->id);

                if (
                    $draft->ticket_id !== $ticket->id ||
                    $draft->user_id !== $author->user_id ||
                    $draft->type !== MessageType::Internal
                ) {
                    throw new DomainException(
                        "The draft does not belong to this user or ticket.",
                    );
                }
            }

            $message = $ticket->addInternalNote($author, $content);

            if ($draft !== null) {
                $attachments = $draft->attachments()->lockForUpdate()->get();

                foreach ($attachments as $attachment) {
                    $attachment->moveFromDraftToMessage($draft, $message);
                }

                $draft->delete();
            }

            return $message;
        });
    }
}
