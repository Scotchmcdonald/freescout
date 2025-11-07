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
            <?php echo e(__('Edit Customer')); ?> - <?php echo e($customer->getFullName()); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <?php if($errors->any()): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(session('success')): ?>
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                            <p class="text-sm text-green-700"><?php echo e(session('success')); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo e(route('customers.update', $customer)); ?>" id="customerForm">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('First Name')); ?> *
                                    </label>
                                    <input type="text" name="first_name" id="first_name" required
                                           value="<?php echo e(old('first_name', $customer->first_name)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('Last Name')); ?>

                                    </label>
                                    <input type="text" name="last_name" id="last_name"
                                           value="<?php echo e(old('last_name', $customer->last_name)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div id="emails-container">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo e(__('Email Addresses')); ?>

                                </label>
                                <?php
                                    $emails = old('emails', $customer->emails ?? [['email' => '', 'type' => 'work']]);
                                ?>
                                <?php $__currentLoopData = $emails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $email): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="email-row flex gap-2 mb-2">
                                        <input type="email" name="emails[<?php echo e($index); ?>][email]"
                                               value="<?php echo e(is_array($email) ? ($email['email'] ?? '') : $email); ?>"
                                               placeholder="email@example.com"
                                               class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <select name="emails[<?php echo e($index); ?>][type]"
                                                class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="work" <?php echo e((is_array($email) && ($email['type'] ?? '') == 'work') ? 'selected' : ''); ?>>Work</option>
                                            <option value="home" <?php echo e((is_array($email) && ($email['type'] ?? '') == 'home') ? 'selected' : ''); ?>>Home</option>
                                            <option value="other" <?php echo e((is_array($email) && ($email['type'] ?? '') == 'other') ? 'selected' : ''); ?>>Other</option>
                                        </select>
                                        <?php if($index > 0): ?>
                                            <button type="button" onclick="removeEmail(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" onclick="addEmail()" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                    + Add another email
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="company" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('Company')); ?>

                                    </label>
                                    <input type="text" name="company" id="company"
                                           value="<?php echo e(old('company', $customer->company)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="job_title" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('Job Title')); ?>

                                    </label>
                                    <input type="text" name="job_title" id="job_title"
                                           value="<?php echo e(old('job_title', $customer->job_title)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo e(__('Address')); ?>

                                </label>
                                <input type="text" name="address" id="address"
                                       value="<?php echo e(old('address', $customer->address)); ?>"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('City')); ?>

                                    </label>
                                    <input type="text" name="city" id="city"
                                           value="<?php echo e(old('city', $customer->city)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('State/Province')); ?>

                                    </label>
                                    <input type="text" name="state" id="state"
                                           value="<?php echo e(old('state', $customer->state)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="zip" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('ZIP/Postal Code')); ?>

                                    </label>
                                    <input type="text" name="zip" id="zip"
                                           value="<?php echo e(old('zip', $customer->zip)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo e(__('Country')); ?>

                                </label>
                                <input type="text" name="country" id="country" maxlength="2"
                                       value="<?php echo e(old('country', $customer->country)); ?>"
                                       placeholder="US"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo e(__('Notes')); ?>

                                </label>
                                <textarea name="notes" id="notes" rows="4"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo e(old('notes', $customer->notes)); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-between">
                            <a href="<?php echo e(route('customers.show', $customer)); ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                <?php echo e(__('Cancel')); ?>

                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <?php echo e(__('Save Customer')); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let emailIndex = <?php echo e(count($emails)); ?>;
        
        function addEmail() {
            const container = document.getElementById('emails-container');
            const newRow = document.createElement('div');
            newRow.className = 'email-row flex gap-2 mb-2';
            newRow.innerHTML = `
                <input type="email" name="emails[${emailIndex}][email]"
                       placeholder="email@example.com"
                       class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <select name="emails[${emailIndex}][type]"
                        class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="work">Work</option>
                    <option value="home">Home</option>
                    <option value="other">Other</option>
                </select>
                <button type="button" onclick="removeEmail(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.insertBefore(newRow, container.querySelector('button'));
            emailIndex++;
        }
        
        function removeEmail(button) {
            button.closest('.email-row').remove();
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
<?php /**PATH /home/runner/work/freescout/freescout/resources/views/customers/edit.blade.php ENDPATH**/ ?>