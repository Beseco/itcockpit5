<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'IT Cockpit')); ?></title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

        <?php echo $__env->yieldPushContent('styles'); ?>
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            <!-- Sidebar -->
            <div 
                x-show="sidebarOpen" 
                @click="sidebarOpen = false"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
                style="display: none;"
            ></div>

            <!-- Sidebar for mobile -->
            <div 
                x-show="sidebarOpen"
                @click.away="sidebarOpen = false"
                x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 lg:hidden"
                style="display: none;"
            >
                <?php echo $__env->make('layouts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <!-- Static sidebar for desktop -->
            <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
                <div class="flex flex-col flex-grow bg-gray-800 overflow-y-auto">
                    <?php echo $__env->make('layouts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>

            <!-- Main content area -->
            <div class="lg:pl-64 flex flex-col flex-1">
                <!-- Top header -->
                <div class="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-white shadow">
                    <!-- Mobile menu button -->
                    <button 
                        @click="sidebarOpen = true"
                        type="button" 
                        class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 lg:hidden"
                    >
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <!-- Header content -->
                    <div class="flex-1 px-4 flex justify-between">
                        <div class="flex-1 flex">
                            <!-- Page title or breadcrumbs can go here -->
                        </div>
                        <div class="ml-4 flex items-center md:ml-6">
                            <!-- User dropdown -->
                            <div x-data="{ userMenuOpen: false }" class="ml-3 relative">
                                <div>
                                    <button 
                                        @click="userMenuOpen = !userMenuOpen"
                                        type="button" 
                                        class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        <span class="sr-only">Open user menu</span>
                                        <div class="flex items-center space-x-3 px-3 py-2">
                                            <div class="flex-shrink-0">
                                                <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold">
                                                    <?php echo e(strtoupper(substr(Auth::user()->name, 0, 1))); ?>

                                                </div>
                                            </div>
                                            <div class="hidden md:block text-left">
                                                <div class="text-sm font-medium text-gray-700"><?php echo e(Auth::user()->name); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo e(ucfirst(Auth::user()->role)); ?></div>
                                            </div>
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </div>
                                    </button>
                                </div>

                                <!-- Dropdown menu -->
                                <div 
                                    x-show="userMenuOpen"
                                    @click.away="userMenuOpen = false"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
                                    style="display: none;"
                                >
                                    <a href="<?php echo e(route('profile.edit')); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <?php echo e(__('Profile')); ?>

                                    </a>
                                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <?php echo e(__('Log Out')); ?>

                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Impersonation Banner -->
                <?php if(session('impersonating_original_id')): ?>
                    <?php $originalUser = \App\Models\User::find(session('impersonating_original_id')); ?>
                    <div class="bg-amber-400 border-b border-amber-500 px-4 py-2 flex items-center justify-between text-sm font-medium text-amber-900">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span>
                                Sie sind als <strong><?php echo e(Auth::user()->name); ?></strong> angemeldet
                                (Originalaccount: <strong><?php echo e($originalUser?->name ?? 'Unbekannt'); ?></strong>)
                            </span>
                        </div>
                        <form method="POST" action="<?php echo e(route('impersonate.stop')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1 bg-amber-900 text-amber-100 rounded-md text-xs font-semibold hover:bg-amber-800 transition">
                                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                </svg>
                                Zurück zu meinem Account
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Main content -->
                <main class="flex-1">
                    <!-- Page Heading -->
                    <?php if(isset($header)): ?>
                        <header class="bg-white shadow">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                <?php echo e($header); ?>

                            </div>
                        </header>
                    <?php endif; ?>

                    <!-- Page Content -->
                    <div>
                        <?php echo e($slot); ?>

                    </div>
                </main>
            </div>
        </div>
        <?php echo $__env->yieldPushContent('scripts'); ?>
    </body>
</html>
<?php /**PATH C:\xampp\htdocs\itcockpit\new\resources\views/layouts/app.blade.php ENDPATH**/ ?>