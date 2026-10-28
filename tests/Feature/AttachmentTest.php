<?php

use App\Exceptions\Domain\InvalidAttachmentException;
use App\Models\Attachment;
use App\Models\Message;
use App\Models\KnowledgeArticle;
use DomainException;

it("can attach a file to a message", function () {
    $message = Message::factory()->create();
    $attachment = Attachment::factory()->create();

    $attachment->attachTo($message);

    expect($attachment->fresh()->attachable)
        ->toBeInstanceOf(Message::class)
        ->and($attachment->fresh()->attachable_id)
        ->toBe($message->id);
});

it("cannot attach a file twice", function () {
    $message = Message::factory()->create();
    $attachment = Attachment::factory()->create();

    $attachment->attachTo($message);
    $attachment->attachTo($message);
})->throws(InvalidAttachmentException::class);

it("can attach a file to a knowledge article", function () {
    $article = KnowledgeArticle::factory()->create();
    $attachment = Attachment::factory()->create();

    $attachment->attachTo($article);

    expect($article->attachments()->count())->toBe(1);
});
