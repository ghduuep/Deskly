<?php

namespace App\Actions\Ticket;

use App\HtmlSanitizer;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

final class AddInternalNote
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function handle(
        Ticket $ticket,
        Contact $author,
        string $content,
    ): Message {
        $content = $this->sanitizer->sanitize($content);

        return DB::transaction(function () use ($ticket, $author, $content) {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            return $ticket->addInternalNote($author, $content);
        });
    }
}
