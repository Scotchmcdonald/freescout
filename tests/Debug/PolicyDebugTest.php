<?php

declare(strict_types=1);

namespace Tests\Debug;

use App\Models\Mailbox;
use App\Models\User;
use Tests\IntegrationTestCase;

class PolicyDebugTest extends IntegrationTestCase
{
    public function test_admin_user_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        dump('Created user:', $admin->toArray());
        dump('User role:', $admin->role);
        dump('ROLE_ADMIN constant:', User::ROLE_ADMIN);
        dump('isAdmin():', $admin->isAdmin());
        
        $this->assertEquals(2, $admin->role);
        $this->assertTrue($admin->isAdmin());
    }
    
    public function test_mailbox_policy_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        dump('User isAdmin:', $admin->isAdmin());
        dump('User role:', $admin->role);
        
        $this->actingAs($admin);
        
        // This should pass for admin
        $this->assertTrue($admin->can('view', $mailbox));
    }
    
    public function test_mailbox_policy_with_request_resolver(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $request = \Illuminate\Http\Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $admin);
        
        dump('Request user:', $request->user());
        dump('Request user isAdmin:', $request->user()->isAdmin());
        
        // Try to authorize via request
        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $gate->forUser($request->user());
        
        $canView = $gate->forUser($request->user())->allows('view', $mailbox);
        dump('Can view mailbox:', $canView);
        
        $this->assertTrue($canView);
    }
    
    public function test_controller_authorization_pattern(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $admin->mailboxes()->attach($mailbox->id);
        
        // Mimic what ControllerCoverageTest does
        $request = \Illuminate\Http\Request::create('/conversations/clone', 'POST');
        $request->setUserResolver(fn () => $admin);
        
        dump('Admin ID:', $admin->id);
        dump('Admin role:', $admin->role);
        dump('Admin isAdmin:', $admin->isAdmin());
        dump('Admin mailboxes count:', $admin->mailboxes()->count());
        
        // Now try what the controller does
        $controller = new \App\Http\Controllers\ConversationController;
        
        try {
            // This is what fails in the controller
            $this->assertTrue($admin->isAdmin()); // Should pass
            dump('About to authorize...');
            
            // Manually test authorization
            $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
            $result = $gate->forUser($request->user())->inspect('view', $mailbox);
            dump('Authorization result:', $result);
            
        } catch (\Exception $e) {
            dump('Exception:', $e->getMessage());
            throw $e;
        }
    }
    
    public function test_actual_controller_clone_call(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Models\Mailbox::factory()->create();
        $admin->mailboxes()->attach($mailbox->id);
        
        $customer = \App\Models\Customer::factory()->create();
        $originalConversation = \App\Models\Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
        ]);
        
        $thread = \App\Models\Thread::factory()->create([
            'conversation_id' => $originalConversation->id,
            'customer_id' => $customer->id,
        ]);

        $request = \Illuminate\Http\Request::create('/conversations/clone', 'POST');
        $request->setUserResolver(fn () => $admin);

        dump('Calling controller...');
        $controller = new \App\Http\Controllers\ConversationController;
        
        try {
            $response = $controller->clone($request, $mailbox, $thread);
            dump('Success! Response:', get_class($response));
            $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            dump('Authorization failed:', $e->getMessage());
            dump('User from request:', $request->user()->toArray());
            dump('User isAdmin:', $request->user()->isAdmin());
            dump('Auth guard user:', auth()->user());
            throw $e;
        }
    }
    
    public function test_with_acting_as(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Models\Mailbox::factory()->create();
        $admin->mailboxes()->attach($mailbox->id);
        
        $customer = \App\Models\Customer::factory()->create();
        $originalConversation = \App\Models\Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
        ]);
        
        $thread = \App\Models\Thread::factory()->create([
            'conversation_id' => $originalConversation->id,
            'customer_id' => $customer->id,
        ]);

        // Use actingAs instead of setUserResolver
        $this->actingAs($admin);

        $request = \Illuminate\Http\Request::create('/conversations/clone', 'POST');
        $request->setUserResolver(fn () => $admin);

        $controller = new \App\Http\Controllers\ConversationController;
        $response = $controller->clone($request, $mailbox, $thread);
        
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }
}
