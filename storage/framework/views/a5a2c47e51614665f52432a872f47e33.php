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
            <?php echo e(__('Announcement Management')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Create Button -->
            <div class="mb-4">
                <a href="<?php echo e(route('announcements.create')); ?>" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <?php echo e(__('Create New Announcement')); ?>

                </a>
            </div>
            <?php if(session('success')): ?>
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" 
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php echo e(__(session('success'))); ?>

                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" 
                     class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php echo e(__(session('error'))); ?>

                </div>
            <?php endif; ?>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Announcements Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('Type')); ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('Message')); ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('Starts At')); ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('Ends At')); ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('Status')); ?></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('Actions')); ?></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $__empty_1 = true; $__currentLoopData = $announcements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php if($announcement->type === 'critical'): ?> bg-red-100 text-red-800
                                                <?php elseif($announcement->type === 'maintenance'): ?> bg-yellow-100 text-yellow-800
                                                <?php else: ?> bg-blue-100 text-blue-800
                                                <?php endif; ?>">
                                                <?php echo e(__(ucfirst($announcement->type))); ?>

                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-md truncate"><?php echo e($announcement->message); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo e($announcement->starts_at ? $announcement->starts_at->format('d.m.Y H:i') : __('N/A')); ?>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo e($announcement->ends_at ? $announcement->ends_at->format('d.m.Y H:i') : __('N/A')); ?>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if($announcement->is_fixed): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    <?php echo e(__('Fixed')); ?>

                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    <?php echo e(__('Active')); ?>

                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" x-data="{ showDeleteConfirm: false }">
                                            <a href="<?php echo e(route('announcements.edit', $announcement)); ?>" class="text-indigo-600 hover:text-indigo-900 mr-3"><?php echo e(__('Edit')); ?></a>
                                            
                                            <?php if($announcement->type === 'critical' && !$announcement->is_fixed): ?>
                                                <form action="<?php echo e(route('announcements.mark-as-fixed', $announcement)); ?>" method="POST" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="text-green-600 hover:text-green-900 mr-3">
                                                        <?php echo e(__('Mark as Fixed')); ?>

                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <button @click="showDeleteConfirm = true" type="button" class="text-red-600 hover:text-red-900">
                                                <?php echo e(__('Delete')); ?>

                                            </button>
                                            
                                            <!-- Delete Confirmation Modal -->
                                            <div x-show="showDeleteConfirm" 
                                                 x-cloak
                                                 @click.away="showDeleteConfirm = false"
                                                 class="fixed inset-0 z-50 overflow-y-auto" 
                                                 style="display: none;">
                                                <div class="flex items-center justify-center min-h-screen px-4">
                                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                                                    
                                                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                        <h3 class="text-lg font-medium text-gray-900 mb-4"><?php echo e(__('Confirm Delete')); ?></h3>
                                                        <p class="text-sm text-gray-500 mb-4">
                                                            <?php echo e(__('Are you sure you want to delete this announcement? This action cannot be undone.')); ?>

                                                        </p>
                                                        <div class="flex justify-end space-x-3">
                                                            <button @click="showDeleteConfirm = false" 
                                                                    type="button" 
                                                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                                                <?php echo e(__('Cancel')); ?>

                                                            </button>
                                                            <form action="<?php echo e(route('announcements.destroy', $announcement)); ?>" method="POST" class="inline">
                                                                <?php echo csrf_field(); ?>
                                                                <?php echo method_field('DELETE'); ?>
                                                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                                    <?php echo e(__('Delete')); ?>

                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500"><?php echo e(__('No announcements found.')); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        <?php echo e($announcements->links()); ?>

                    </div>
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
<?php /**PATH C:\xampp\htdocs\itcockpit\new\resources\views/announcements/index.blade.php ENDPATH**/ ?>