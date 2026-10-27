<?php

use App\Actions\Attachment\StoreAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it("stores an uploaded file and creates an attachment record", function () {
    Storage::fake("local");

    $user = User::factory()->create();

    $file = UploadedFile::fake()->create(
        "document.pdf",
        100,
        "application/pdf",
    );

    $attachment = app(StoreAttachment::class)->handle($file, $user);

    Storage::disk("local")->assertExists($attachment->path);

    expect($attachment->disk)
        ->toBe("local")
        ->and($attachment->original_name)
        ->toBe("document.pdf")
        ->and($attachment->uploaded_by_id)
        ->toBe($user->id)
        ->and($attachment->attachable_type)
        ->toBeNull()
        ->and($attachment->attachable_id)
        ->toBeNull();
});
