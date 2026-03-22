<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Example Module</h3>
            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        </div>
        
        <p class="text-gray-600 text-sm mb-4">
            This is an example dashboard widget demonstrating module integration.
        </p>

        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <span class="text-sm text-gray-700">Module Status</span>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                    Active
                </span>
            </div>

            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <span class="text-sm text-gray-700">Version</span>
                <span class="text-sm font-medium text-gray-900">1.0.0</span>
            </div>

            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <span class="text-sm text-gray-700">Last Updated</span>
                <span class="text-sm font-medium text-gray-900"><?php echo e(now()->format('M d, Y')); ?></span>
            </div>
        </div>

        <div class="mt-4">
            <a href="<?php echo e(route('example.index')); ?>" 
               class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded transition-colors">
                View Module
            </a>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\itcockpit\new\app\Modules/Example/Views/widget.blade.php ENDPATH**/ ?>