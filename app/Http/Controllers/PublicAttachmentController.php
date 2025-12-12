<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicAttachmentController extends Controller
{
    public function download(Request $request, int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $attachment = Attachment::findOrFail($id);

        // Check if file exists
        if (!Storage::exists($attachment->file_dir . '/' . $attachment->file_name)) {
            // Try local disk if not using default
             if (!file_exists(storage_path('app/' . $attachment->file_dir . '/' . $attachment->file_name))) {
                 abort(404);
             }
             $path = storage_path('app/' . $attachment->file_dir . '/' . $attachment->file_name);
             return response()->download($path, $attachment->file_name);
        }

        return Storage::download($attachment->file_dir . '/' . $attachment->file_name, $attachment->file_name);
    }
}
