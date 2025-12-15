<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Encrypt existing PAT if it exists
        $pat = DB::table('options')->where('name', 'github_personal_access_token')->first();
        if ($pat && isset($pat->value) && is_string($pat->value)) {
            try {
                // Check if already encrypted by trying to decrypt
                Crypt::decryptString($pat->value);
            } catch (\Exception $e) {
                // Not encrypted, so encrypt it
                $encrypted = Crypt::encryptString($pat->value);
                DB::table('options')
                    ->where('name', 'github_personal_access_token')
                    ->update(['value' => $encrypted]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Decrypt PAT if it exists
        $pat = DB::table('options')->where('name', 'github_personal_access_token')->first();
        if ($pat && isset($pat->value) && is_string($pat->value)) {
            try {
                $decrypted = Crypt::decryptString($pat->value);
                DB::table('options')
                    ->where('name', 'github_personal_access_token')
                    ->update(['value' => $decrypted]);
            } catch (\Exception $e) {
                // Already decrypted, ignore
            }
        }
    }
};
