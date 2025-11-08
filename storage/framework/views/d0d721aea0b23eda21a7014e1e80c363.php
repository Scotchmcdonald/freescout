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
            <?php echo e(__('Edit User')); ?> - <?php echo e($user->getFullName()); ?>

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
                    
                    <form method="POST" action="<?php echo e(route('users.update', $user)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('First Name')); ?> *
                                    </label>
                                    <input type="text" name="first_name" id="first_name" required
                                           value="<?php echo e(old('first_name', $user->first_name)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('Last Name')); ?>

                                    </label>
                                    <input type="text" name="last_name" id="last_name"
                                           value="<?php echo e(old('last_name', $user->last_name)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo e(__('Email')); ?> *
                                </label>
                                <input type="email" name="email" id="email" required
                                       value="<?php echo e(old('email', $user->email)); ?>"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo e(__('Password')); ?>

                                </label>
                                <input type="password" name="password" id="password"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">Leave blank to keep current password</p>
                            </div>
                            
                            <?php if(auth()->user()->isAdmin()): ?>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo e(__('Role')); ?> *
                                        </label>
                                        <select name="role" id="role" required
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="2" <?php echo e(old('role', $user->role) == 2 ? 'selected' : ''); ?>>User</option>
                                            <option value="1" <?php echo e(old('role', $user->role) == 1 ? 'selected' : ''); ?>>Admin</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo e(__('Status')); ?> *
                                        </label>
                                        <select name="status" id="status" required
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="1" <?php echo e(old('status', $user->status) == 1 ? 'selected' : ''); ?>>Active</option>
                                            <option value="2" <?php echo e(old('status', $user->status) == 2 ? 'selected' : ''); ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="job_title" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('Job Title')); ?>

                                    </label>
                                    <input type="text" name="job_title" id="job_title"
                                           value="<?php echo e(old('job_title', $user->job_title)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('Phone')); ?>

                                    </label>
                                    <input type="text" name="phone" id="phone"
                                           value="<?php echo e(old('phone', $user->phone)); ?>"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('Timezone')); ?>

                                    </label>
                                    <select name="timezone" id="timezone"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">System Default</option>
                                        <option value="America/New_York" <?php echo e(old('timezone', $user->timezone) == 'America/New_York' ? 'selected' : ''); ?>>America/New_York</option>
                                        <option value="America/Chicago" <?php echo e(old('timezone', $user->timezone) == 'America/Chicago' ? 'selected' : ''); ?>>America/Chicago</option>
                                        <option value="America/Denver" <?php echo e(old('timezone', $user->timezone) == 'America/Denver' ? 'selected' : ''); ?>>America/Denver</option>
                                        <option value="America/Los_Angeles" <?php echo e(old('timezone', $user->timezone) == 'America/Los_Angeles' ? 'selected' : ''); ?>>America/Los_Angeles</option>
                                        <option value="Europe/London" <?php echo e(old('timezone', $user->timezone) == 'Europe/London' ? 'selected' : ''); ?>>Europe/London</option>
                                        <option value="Europe/Paris" <?php echo e(old('timezone', $user->timezone) == 'Europe/Paris' ? 'selected' : ''); ?>>Europe/Paris</option>
                                        <option value="Asia/Tokyo" <?php echo e(old('timezone', $user->timezone) == 'Asia/Tokyo' ? 'selected' : ''); ?>>Asia/Tokyo</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="locale" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo e(__('Language')); ?>

                                    </label>
                                    <select name="locale" id="locale"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">System Default</option>
                                        <option value="en" <?php echo e(old('locale', $user->locale) == 'en' ? 'selected' : ''); ?>>English</option>
                                        <option value="es" <?php echo e(old('locale', $user->locale) == 'es' ? 'selected' : ''); ?>>Spanish</option>
                                        <option value="fr" <?php echo e(old('locale', $user->locale) == 'fr' ? 'selected' : ''); ?>>French</option>
                                        <option value="de" <?php echo e(old('locale', $user->locale) == 'de' ? 'selected' : ''); ?>>German</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-between">
                            <a href="<?php echo e(route('users.show', $user)); ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                <?php echo e(__('Cancel')); ?>

                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <?php echo e(__('Save Changes')); ?>

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
<?php /**PATH /var/www/html/resources/views/users/edit.blade.php ENDPATH**/ ?>