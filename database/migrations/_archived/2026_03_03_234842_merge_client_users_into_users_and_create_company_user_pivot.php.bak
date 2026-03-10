<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create or augment company_user pivot table
        if (!Schema::hasTable('company_user')) {
            Schema::create('company_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
                $table->enum('status', ['pending', 'approved', 'blocked'])->default('pending');
                $table->unsignedBigInteger('client_id')->nullable(); // Legacy mapping
                $table->boolean('is_primary')->default(true);
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_approver')->default(false);
                $table->decimal('approval_limit', 15, 2)->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'company_id']);
            });
        } else {
            // Table already exists (created by RBAC migration) — add missing columns
            Schema::table('company_user', function (Blueprint $table) {
                if (!Schema::hasColumn('company_user', 'is_primary')) {
                    $table->boolean('is_primary')->default(true)->after('status');
                }
                if (!Schema::hasColumn('company_user', 'client_id')) {
                    $table->unsignedBigInteger('client_id')->nullable()->after('is_primary');
                }
                if (!Schema::hasColumn('company_user', 'manager_id')) {
                    $table->unsignedBigInteger('manager_id')->nullable()->after('client_id');
                }
                if (!Schema::hasColumn('company_user', 'is_approver')) {
                    $table->boolean('is_approver')->default(false)->after('manager_id');
                }
                if (!Schema::hasColumn('company_user', 'approval_limit')) {
                    $table->decimal('approval_limit', 15, 2)->nullable()->after('is_approver');
                }
            });
        }

        // 2. Migrate data from client_users to users
        if (Schema::hasTable('client_users')) {
            $clientUsers = DB::table('client_users')->get();
            $clientRole = DB::table('roles')->where('name', 'Client User')->first();
            $clientRoleId = $clientRole ? $clientRole->id : 5; // Fallback to 5 if not found
            
            // Map to store old client_user.id => new users.id for manager_id mapping
            $userIdMap = [];
            
            foreach ($clientUsers as $cu) {
                // Determine first and last name
                $nameParts = explode(' ', trim($cu->name), 2);
                $firstName = Str::limit($nameParts[0] ?: 'Unknown', 20, '');
                $lastName = Str::limit($nameParts[1] ?? 'User', 30, '');
                
                // Check if user already exists
                $existingUser = DB::table('users')->where('email', $cu->email)->first();
                
                if ($existingUser) {
                    $newUserId = $existingUser->id;
                } else {
                    $newUserId = DB::table('users')->insertGetId([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $cu->email,
                        'password' => $cu->password ?? bcrypt(Str::random(16)),
                        'email_verified_at' => $cu->email_verified_at,
                        'job_title' => $cu->job_title,
                        'role' => 1, // default
                        'type' => 2, // 2: Client/External just in case
                        'status' => $cu->is_active ? 1 : 2,
                        'created_at' => $cu->created_at,
                        'updated_at' => $cu->updated_at,
                    ]);
                    
                    // Assign RBAC Role
                    DB::table('role_user')->insert([
                        'user_id' => $newUserId,
                        'role_id' => $clientRoleId,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                $userIdMap[$cu->id] = $newUserId;
                
                // Assign to Company
                if ($cu->company_id || $cu->client_id) {
                    // Check if already mapped
                    $exists = DB::table('company_user')
                        ->where('user_id', $newUserId)
                        ->where('company_id', $cu->company_id ?? 0)
                        ->first();
                        
                    if (!$exists && $cu->company_id) {
                        DB::table('company_user')->insert([
                            'user_id' => $newUserId,
                            'company_id' => $cu->company_id,
                            'client_id' => $cu->client_id, // For phase 1 compat
                            'is_primary' => true,
                            'is_approver' => $cu->is_approver,
                            'approval_limit' => $cu->approval_limit,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
            
            // Second pass: update manager_id
            foreach ($clientUsers as $cu) {
                if ($cu->manager_id && isset($userIdMap[$cu->manager_id]) && isset($userIdMap[$cu->id])) {
                    DB::table('company_user')
                        ->where('user_id', $userIdMap[$cu->id])
                        ->update(['manager_id' => $userIdMap[$cu->manager_id]]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user');
        // Note: we don't reverse the user data migration as it could cause data loss
    }
};
