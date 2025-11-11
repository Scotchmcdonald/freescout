<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class AttachmentObserver
{
    /**
     * Handle the Attachment "deleting" event.
     */
    public function deleting(Attachment $attachment): void
    {
        // Delete file from storage
        if ($attachment->file_dir && $attachment->file_name) {
            $filePath = $attachment->file_dir.'/'.$attachment->file_name;
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
        }
    }
}
