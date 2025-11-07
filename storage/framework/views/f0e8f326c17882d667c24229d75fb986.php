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
            <?php echo e(__('General Settings')); ?>

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
                    
                    <form method="POST" action="<?php echo e(route('settings.update')); ?>">
                        <?php echo csrf_field(); ?>
                        
                        <div class="space-y-6">
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo e(__('Company Name')); ?>

                                </label>
                                <input type="text" name="company_name" id="company_name"
                                       value="<?php echo e(old('company_name', $settings['company_name'] ?? '')); ?>"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">
                                    Used in email signatures and customer-facing communications
                                </p>
                            </div>
                            
                            <div>
                                <label for="next_ticket" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo e(__('Next Conversation Number')); ?>

                                </label>
                                <input type="number" name="next_ticket" id="next_ticket" min="1"
                                       value="<?php echo e(old('next_ticket', $settings['next_ticket'] ?? 1)); ?>"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">
                                    Internal tracking number for conversations (not visible to customers)
                                </p>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4"><?php echo e(__('Email Settings')); ?></h3>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="email_branding" id="email_branding" value="1"
                                               <?php echo e(old('email_branding', $settings['email_branding'] ?? false) ? 'checked' : ''); ?>

                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <label for="email_branding" class="ml-2 text-sm text-gray-700">
                                            <?php echo e(__('Include company branding in emails')); ?>

                                        </label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="checkbox" name="open_tracking" id="open_tracking" value="1"
                                               <?php echo e(old('open_tracking', $settings['open_tracking'] ?? false) ? 'checked' : ''); ?>

                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <label for="open_tracking" class="ml-2 text-sm text-gray-700">
                                            <?php echo e(__('Track email opens')); ?>

                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4"><?php echo e(__('Customer Data')); ?></h3>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="enrich_customer_data" id="enrich_customer_data" value="1"
                                           <?php echo e(old('enrich_customer_data', $settings['enrich_customer_data'] ?? false) ? 'checked' : ''); ?>

                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label for="enrich_customer_data" class="ml-2 text-sm text-gray-700">
                                        <?php echo e(__('Automatically enrich customer profiles with public data')); ?>

                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <?php echo e(__('Save Settings')); ?>

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
<?php /**PATH /var/www/html/resources/views/settings/index.blade.php ENDPATH**/ ?>