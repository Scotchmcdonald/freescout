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
            <?php echo e(__('Email Settings')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <?php if(session('success')): ?>
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                            <p class="text-sm text-green-700"><?php echo e(session('success')); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($errors->any()): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4">
                        <p class="text-sm text-blue-700">
                            <?php echo e(__('These settings are used to send system emails (alerts and notifications).')); ?>

                        </p>
                    </div>
                    
                    <form method="POST" action="<?php echo e(route('settings.email.update')); ?>">
                        <?php echo csrf_field(); ?>
                        
                        <div class="space-y-6">
                            <div>
                                <label for="mail_driver" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo e(__('Mail Driver')); ?> *
                                </label>
                                <select name="mail_driver" id="mail_driver" required
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="smtp" <?php echo e(old('mail_driver', $settings['mail_driver'] ?? 'smtp') == 'smtp' ? 'selected' : ''); ?>>SMTP</option>
                                    <option value="sendmail" <?php echo e(old('mail_driver', $settings['mail_driver'] ?? '') == 'sendmail' ? 'selected' : ''); ?>>Sendmail</option>
                                    <option value="mailgun" <?php echo e(old('mail_driver', $settings['mail_driver'] ?? '') == 'mailgun' ? 'selected' : ''); ?>>Mailgun</option>
                                    <option value="ses" <?php echo e(old('mail_driver', $settings['mail_driver'] ?? '') == 'ses' ? 'selected' : ''); ?>>Amazon SES</option>
                                    <option value="postmark" <?php echo e(old('mail_driver', $settings['mail_driver'] ?? '') == 'postmark' ? 'selected' : ''); ?>>Postmark</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('From Email')); ?> *
                                    </label>
                                    <input type="email" name="mail_from_address" id="mail_from_address" required
                                           value="<?php echo e(old('mail_from_address', $settings['mail_from_address'] ?? '')); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('From Name')); ?> *
                                    </label>
                                    <input type="text" name="mail_from_name" id="mail_from_name" required
                                           value="<?php echo e(old('mail_from_name', $settings['mail_from_name'] ?? '')); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4"><?php echo e(__('SMTP Settings')); ?></h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo e(__('SMTP Host')); ?>

                                        </label>
                                        <input type="text" name="mail_host" id="mail_host"
                                               value="<?php echo e(old('mail_host', $settings['mail_host'] ?? '')); ?>"
                                               placeholder="smtp.example.com"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-2">
                                                <?php echo e(__('SMTP Port')); ?>

                                            </label>
                                            <input type="number" name="mail_port" id="mail_port"
                                                   value="<?php echo e(old('mail_port', $settings['mail_port'] ?? 587)); ?>"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        
                                        <div class="col-span-2">
                                            <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-2">
                                                <?php echo e(__('Encryption')); ?>

                                            </label>
                                            <select name="mail_encryption" id="mail_encryption"
                                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">None</option>
                                                <option value="tls" <?php echo e(old('mail_encryption', $settings['mail_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''); ?>>TLS</option>
                                                <option value="ssl" <?php echo e(old('mail_encryption', $settings['mail_encryption'] ?? '') == 'ssl' ? 'selected' : ''); ?>>SSL</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo e(__('SMTP Username')); ?>

                                        </label>
                                        <input type="text" name="mail_username" id="mail_username"
                                               value="<?php echo e(old('mail_username', $settings['mail_username'] ?? '')); ?>"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo e(__('SMTP Password')); ?>

                                        </label>
                                        <input type="password" name="mail_password" id="mail_password"
                                               value="<?php echo e(old('mail_password', $settings['mail_password'] ?? '')); ?>"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <p class="mt-1 text-sm text-gray-500">Leave blank to keep current password</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <?php echo e(__('Save Email Settings')); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
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
<?php /**PATH /var/www/html/resources/views/settings/email.blade.php ENDPATH**/ ?>