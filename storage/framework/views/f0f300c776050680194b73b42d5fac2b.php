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
        <h2 class="text-xl font-semibold text-gray-800">Persönlicher Bereich</h2>
     <?php $__env->endSlot(); ?>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            
            <div class="lg:col-span-2 space-y-6">

                
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 flex items-center gap-5">
                        <div class="flex-shrink-0 h-16 w-16 rounded-full bg-indigo-600 flex items-center justify-center text-white text-2xl font-bold">
                            <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xl font-bold text-gray-900"><?php echo e($user->name); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo e($user->email); ?></p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                <?php $__currentLoopData = $user->getRoleNames(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                        <?php echo e($role); ?>

                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                        <?php if($user->last_login_at): ?>
                        <div class="flex-shrink-0 text-xs text-gray-400 text-right">
                            Letzter Login:<br>
                            <?php echo e($user->last_login_at->format('d.m.Y H:i')); ?>

                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-900">Meine Gruppen</h3>
                    </div>
                    <?php if($user->gruppen->isEmpty()): ?>
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keiner Gruppe zugeordnet.</div>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Gruppe</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rollen der Gruppe</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php $__currentLoopData = $user->gruppen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gruppe): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-800"><?php echo e($gruppe->name); ?></td>
                                    <td class="px-4 py-2">
                                        <?php $__empty_1 = true; $__currentLoopData = $gruppe->roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 mr-1">
                                                <?php echo e($role->name); ?>

                                            </span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <span class="text-gray-400 text-xs">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Meine Bestellungen</h3>
                        <?php if($bestellungen->isNotEmpty()): ?>
                            <a href="<?php echo e(route('orders.index')); ?>" class="text-xs text-indigo-600 hover:text-indigo-800">Alle ansehen →</a>
                        <?php endif; ?>
                    </div>
                    <?php if($bestellungen->isEmpty()): ?>
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keine Bestellungen vorhanden.</div>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Artikel</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Preis</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php $__currentLoopData = $bestellungen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bestellung): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap"><?php echo e($bestellung->order_date->format('d.m.Y')); ?></td>
                                    <td class="px-4 py-2 text-gray-800 max-w-xs truncate"><?php echo e($bestellung->subject); ?></td>
                                    <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap"><?php echo e(number_format($bestellung->price_gross, 2, ',', '.')); ?> €</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            <?php echo e($bestellung->status == 6 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo e(\App\Models\Order::STATUS_LABELS[$bestellung->status] ?? $bestellung->status); ?>

                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Aktuelle Ankündigungen</h3>
                        <a href="<?php echo e(route('announcements.index')); ?>" class="text-xs text-indigo-600 hover:text-indigo-800">Alle ansehen →</a>
                    </div>
                    <?php if($ankuendigungen->isEmpty()): ?>
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keine aktiven Ankündigungen.</div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-100">
                            <?php $__currentLoopData = $ankuendigungen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="px-6 py-3 flex items-start gap-3">
                                <span class="flex-shrink-0 mt-0.5 w-2 h-2 rounded-full
                                    <?php echo e($a->type === 'critical' ? 'bg-red-500' : ($a->type === 'maintenance' ? 'bg-yellow-500' : 'bg-blue-400')); ?>">
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-700 line-clamp-2"><?php echo e($a->message); ?></p>
                                    <p class="text-xs text-gray-400 mt-0.5"><?php echo e($a->starts_at->format('d.m.Y H:i')); ?></p>
                                </div>
                            </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </div>

            </div>

            
            <div class="space-y-6">

                
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-900">Meine Rollen & Aufgaben</h3>
                    </div>
                    <?php if($aufgabenZuweisungen->isEmpty()): ?>
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keine Aufgaben zugeordnet.</div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-100">
                            <?php $__currentLoopData = $aufgabenZuweisungen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $az): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-800"><?php echo e($az->aufgabe?->name ?? '—'); ?></p>
                                <div class="mt-1 flex items-center gap-2 flex-wrap">
                                    <?php if($az->gruppe): ?>
                                        <span class="text-xs text-gray-500"><?php echo e($az->gruppe->name); ?></span>
                                    <?php endif; ?>
                                    <?php if($az->admin_user_id === Auth::id()): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">Admin</span>
                                    <?php endif; ?>
                                    <?php if($az->stellvertreter_user_id === Auth::id()): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Stellvertreter</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </div>

                
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-900">Meine Stelle</h3>
                    </div>
                    <?php if(!$stelle): ?>
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keine Stelle zugeordnet.</div>
                    <?php else: ?>
                        <div class="px-5 py-4 space-y-3">
                            <div>
                                <p class="text-base font-semibold text-gray-900"><?php echo e($stelle->bezeichnung); ?></p>
                                <?php if($stelle->stellennummer): ?>
                                    <p class="text-xs font-mono text-gray-500 mt-0.5"><?php echo e($stelle->stellennummer); ?></p>
                                <?php endif; ?>
                            </div>

                            <dl class="space-y-1.5 text-sm">
                                <?php if($stelle->gruppe): ?>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Gruppe</dt>
                                    <dd class="text-gray-700 font-medium text-right"><?php echo e($stelle->gruppe->name); ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if($stelle->tvod_bewertung): ?>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">TVöD</dt>
                                    <dd><span class="px-1.5 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800"><?php echo e($stelle->tvod_bewertung); ?></span></dd>
                                </div>
                                <?php endif; ?>
                                <?php if($stelle->stunden): ?>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Stunden</dt>
                                    <dd class="text-gray-700"><?php echo e(number_format($stelle->stunden, 1, ',', '.')); ?> Std.
                                        <span class="text-xs text-gray-400">(<?php echo e($stelle->isVollzeit() ? 'VZ' : 'TZ'); ?>)</span>
                                    </dd>
                                </div>
                                <?php endif; ?>
                            </dl>

                            <?php if($stelle->arbeitsvorgaenge->isNotEmpty()): ?>
                            <div class="pt-2 border-t border-gray-100">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Arbeitsvorgänge</p>
                                <ul class="space-y-1">
                                    <?php $__currentLoopData = $stelle->arbeitsvorgaenge; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $av): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="flex items-center justify-between text-xs">
                                        <span class="text-gray-700 truncate mr-2"><?php echo e($av->betreff); ?></span>
                                        <span class="flex-shrink-0 font-semibold text-indigo-600"><?php echo e($av->anteil); ?>%</span>
                                    </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <a href="<?php echo e(route('stellen.show', $stelle)); ?>"
                               class="block text-center text-xs text-indigo-600 hover:text-indigo-800 pt-2 border-t border-gray-100">
                                Vollständige Beschreibung ansehen →
                            </a>
                        </div>
                    <?php endif; ?>
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
<?php /**PATH C:\xampp\htdocs\itcockpit\new\resources\views/personal/index.blade.php ENDPATH**/ ?>