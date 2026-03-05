<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        $customers = DB::table('customers')->get();
        $clientRole = DB::table('roles')->where('name', 'Client User')->first();
        $clientRoleId = $clientRole ? $clientRole->id : 5;
        
        $customerUserMap = [];

        foreach ($customers as $customer) {
            // Find main email
            $mainEmail = DB::table('emails')
                ->where('customer_id', $customer->id)
                ->orderBy('type', 'asc') // Primary first
                ->first();
                
            $emailStr = $mainEmail ? $mainEmail->email : 'customer_'.$customer->id.'@unknown.local';
            
            // Check if user already exists
            $existingUser = DB::table('users')->where('email', $emailStr)->first();
            
            if ($existingUser) {
                $userId = $existingUser->id;
            } else {
                $userId = DB::table('users')->insertGetId([
                    'first_name' => Str::limit($customer->first_name ?: 'Unknown', 20, ''),
                    'last_name' => Str::limit($customer->last_name ?: 'User', 30, ''),
                    'email' => $emailStr,
                    'password' => bcrypt(Str::random(16)),
                    'role' => 1,
                    'type' => 2, // External
                    'status' => 1,
                    'photo_url' => $customer->photo_url,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                ]);
                
                // Assign RBAC Role
                DB::table('role_user')->insert([
                    'user_id' => $userId,
                    'role_id' => $clientRoleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            $customerUserMap[$customer->id] = clone (object)[
                'user_id' => $userId,
                'email' => $emailStr,
                'name' => trim($customer->first_name . ' ' . $customer->last_name)
            ];
        }

        // Now update conversations and threads
        foreach ($customerUserMap as $oldCustomerId => $data) {
            DB::table('conversations')
                ->where('customer_id', $oldCustomerId)
                ->update([
                    'client_user_id' => $data->user_id,
                    'sender_email' => $data->email,
                    'sender_name' => $data->name,
                ]);
                
            DB::table('threads')
                ->where('customer_id', $oldCustomerId)
                ->update([
                    'client_user_id' => $data->user_id,
                    'sender_email' => $data->email,
                    'sender_name' => $data->name,
                ]);
        }
    }

    public function down(): void
    {
    }
};
