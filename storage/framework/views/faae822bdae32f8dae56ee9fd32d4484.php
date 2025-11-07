<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mailbox Settings: <?php echo e($mailbox->name); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Connection Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Connection Settings</h3>
                    <div class="flex space-x-4">
                        <a href="<?php echo e(route('mailboxes.connection.incoming', $mailbox)); ?>"
                           class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                            Incoming (IMAP)
                        </a>
                        <a href="<?php echo e(route('mailboxes.connection.outgoing', $mailbox)); ?>"
                           class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                            Outgoing (SMTP)
                        </a>
                    </div>
                </div>
            </div>

            <!-- SMTP Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Test Outgoing Mail (SMTP)</h3>
                        <?php if(!empty($mailbox->out_server)): ?>
                            <button onclick="testSmtp()"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Test Connection
                            </button>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">Configure outgoing mail to test.</p>
                        <?php endif; ?>
                    </div>

                    <div id="smtp-result" class="mt-4 hidden"></div>

                    <!-- Test Email Form -->
                    <div id="smtp-test-form" class="mt-4 hidden">
                        <div class="flex items-center space-x-2">
                            <input type="email"
                                   id="test-email"
                                   placeholder="Enter test email address"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <button onclick="sendTestEmail()"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Send Test Email
                            </button>
                            <button onclick="cancelSmtpTest()"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IMAP Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Test Incoming Mail (IMAP)</h3>
                        <?php if(!empty($mailbox->in_server)): ?>
                            <button onclick="testImap()"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Test Connection
                            </button>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">Configure incoming mail to test.</p>
                        <?php endif; ?>
                    </div>

                    <div id="imap-result" class="mt-4 hidden"></div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    
                    <div class="flex space-x-4">
                        <button onclick="fetchEmails()" 
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Fetch Emails Now
                        </button>
                        
                        <a href="<?php echo e(route('mailboxes.view', $mailbox)); ?>" 
                           class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                            View Conversations
                        </a>
                    </div>
                    
                    <div id="fetch-result" class="mt-4 hidden"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function testSmtp() {
            document.getElementById('smtp-test-form').classList.remove('hidden');
            document.getElementById('smtp-result').classList.add('hidden');
        }
        
        function cancelSmtpTest() {
            document.getElementById('smtp-test-form').classList.add('hidden');
            document.getElementById('test-email').value = '';
        }
        
        async function sendTestEmail() {
            const testEmail = document.getElementById('test-email').value;
            if (!testEmail) {
                alert('Please enter a test email address');
                return;
            }
            
            const resultDiv = document.getElementById('smtp-result');
            const button = event.target;
            
            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = '<span class="inline-block animate-spin mr-2">⟳</span> Sending...';
            
            resultDiv.innerHTML = '<p class="text-gray-600">Sending test email...</p>';
            resultDiv.classList.remove('hidden');
            
            try {
                const response = await fetch('<?php echo e(route('settings.test-smtp')); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
                    },
                    body: JSON.stringify({
                        mailbox_id: <?php echo e($mailbox->id); ?>,
                        test_email: testEmail
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = '<div class="p-4 bg-green-100 text-green-800 rounded">' + data.message + '</div>';
                    document.getElementById('smtp-test-form').classList.add('hidden');
                } else {
                    resultDiv.innerHTML = '<div class="p-4 bg-red-100 text-red-800 rounded">' + data.message + '</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="p-4 bg-red-100 text-red-800 rounded">Error: ' + error.message + '</div>';
            } finally {
                // Re-enable button
                button.disabled = false;
                button.innerHTML = 'Send Test Email';
            }
        }
        
        async function testImap() {
            const resultDiv = document.getElementById('imap-result');
            const button = event.target;
            
            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = '<span class="inline-block animate-spin mr-2">⟳</span> Testing...';
            
            resultDiv.innerHTML = '<p class="text-gray-600">Testing IMAP connection...</p>';
            resultDiv.classList.remove('hidden');
            
            try {
                const response = await fetch('<?php echo e(route('settings.test-imap')); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
                    },
                    body: JSON.stringify({
                        mailbox_id: <?php echo e($mailbox->id); ?>

                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = '<div class="p-4 bg-green-100 text-green-800 rounded">' + data.message + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="p-4 bg-red-100 text-red-800 rounded">' + data.message + '</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="p-4 bg-red-100 text-red-800 rounded">Error: ' + error.message + '</div>';
            } finally {
                // Re-enable button
                button.disabled = false;
                button.innerHTML = 'Test Connection';
            }
        }
        
        async function fetchEmails() {
            const resultDiv = document.getElementById('fetch-result');
            const button = event.target;
            
            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = '<span class="inline-block animate-spin mr-2">⟳</span> Fetching...';
            
            resultDiv.innerHTML = '<p class="text-gray-600">Fetching emails from mailbox...</p>';
            resultDiv.classList.remove('hidden');
            
            try {
                const response = await fetch('<?php echo e(route('mailboxes.fetch-emails', $mailbox)); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = '<div class="p-4 bg-green-100 text-green-800 rounded">' + 
                        '<strong>Success!</strong> ' + data.message + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="p-4 bg-red-100 text-red-800 rounded">' + 
                        '<strong>Error:</strong> ' + data.message + '</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="p-4 bg-red-100 text-red-800 rounded">' + 
                    '<strong>Error:</strong> ' + error.message + '</div>';
            } finally {
                // Re-enable button
                button.disabled = false;
                button.innerHTML = 'Fetch Emails Now';
            }
        }
    </script>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH /home/runner/work/freescout/freescout/resources/views/mailboxes/settings.blade.php ENDPATH**/ ?>