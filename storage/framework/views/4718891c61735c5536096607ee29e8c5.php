<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['announcement']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['announcement']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div class="border-l-4 p-4 rounded-lg <?php echo e($announcement->getColorClass()); ?>" x-data="{ showMarkFixed: false }">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <?php if($announcement->isCritical() && !$announcement->isResolved()): ?>
                    
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                <?php elseif($announcement->isCritical() && $announcement->isResolved()): ?>
                    
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                <?php elseif($announcement->type === 'maintenance'): ?>
                    
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <?php else: ?>
                    
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                <?php endif; ?>
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium">
                    <?php echo e(ucfirst($announcement->type)); ?>

                    <?php if($announcement->isResolved()): ?>
                        <span class="text-xs">(Resolved)</span>
                    <?php endif; ?>
                </h3>
                <?php if($announcement->starts_at || $announcement->ends_at): ?>
                    <p class="text-xs">
                        <?php if($announcement->starts_at): ?>
                            From: <?php echo e($announcement->starts_at->format('M d, Y H:i')); ?>

                        <?php endif; ?>
                        <?php if($announcement->ends_at): ?>
                            <?php if($announcement->starts_at): ?> | <?php endif; ?>
                            To: <?php echo e($announcement->ends_at->format('M d, Y H:i')); ?>

                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="mt-2 text-sm">
                <?php echo e($announcement->message); ?>

            </div>
            <?php if($announcement->fixed_at): ?>
                <p class="mt-2 text-xs">
                    Fixed at: <?php echo e($announcement->fixed_at->format('M d, Y H:i')); ?>

                </p>
            <?php endif; ?>
            <?php if($announcement->isCritical() && !$announcement->isResolved() && auth()->user()->isAdmin()): ?>
                <div class="mt-3">
                    <button 
                        @click="showMarkFixed = true" 
                        type="button" 
                        class="text-xs px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                        Mark as Fixed
                    </button>
                </div>

                
                <div 
                    x-show="showMarkFixed" 
                    x-cloak
                    @click.away="showMarkFixed = false"
                    class="fixed inset-0 z-50 overflow-y-auto" 
                    style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                        
                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Mark as Fixed</h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Are you sure you want to mark this critical announcement as fixed? This will change its status to resolved.
                            </p>
                            <div class="flex justify-end space-x-3">
                                <button 
                                    @click="showMarkFixed = false" 
                                    type="button" 
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition-colors">
                                    Cancel
                                </button>
                                <form action="<?php echo e(route('announcements.mark-as-fixed', $announcement)); ?>" method="POST" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <button 
                                        type="submit" 
                                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition-colors">
                                        Confirm
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\itcockpit\new\resources\views/components/announcement-card.blade.php ENDPATH**/ ?>