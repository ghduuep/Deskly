<?php

namespace App\Actions\Attachment;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class StoreAttachment
{
    public function handle(UploadedFile $file, User $uploader): Attachment
    {
        $disk = "local";

        $path = $file->store("attachments", $disk);

        if ($path === false) {
            throw new RuntimeException("Failed to store the attachment.");
        }

        try {
            return Attachment::create([
                "disk" => $disk,
                "path" => $path,
                "original_name" => $file->getClientOriginalName(),
                "mime_type" =>
                    $file->getMimeType() ?? "application/octet-stream",
                "size" => $file->getSize(),
                "uploaded_by_id" => $uploader->id,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }
}
