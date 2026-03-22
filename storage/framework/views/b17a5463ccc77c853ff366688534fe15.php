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
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Applikationen</h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <?php if(session('success')): ?>
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            
            <div class="flex items-center justify-between mb-4 gap-4">
                <form action="<?php echo e(route('applikationen.index')); ?>" method="GET" class="flex gap-2">
                    <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'search','type' => 'text','placeholder' => 'Name, Zweck, SG, Hersteller...','value' => ''.e($search).'','class' => 'w-72']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','type' => 'text','placeholder' => 'Name, Zweck, SG, Hersteller...','value' => ''.e($search).'','class' => 'w-72']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                    <?php if($sort !== 'name'): ?> <input type="hidden" name="sort" value="<?php echo e($sort); ?>"> <?php endif; ?>
                    <?php if($order !== 'ASC'): ?> <input type="hidden" name="order" value="<?php echo e($order); ?>"> <?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>Suchen <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                    <?php if($search): ?>
                        <a href="<?php echo e(route('applikationen.index')); ?>"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            Zurücksetzen
                        </a>
                    <?php endif; ?>
                </form>
                <a href="<?php echo e(route('applikationen.create')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neue Applikation
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <?php $nextOrder = $order === 'ASC' ? 'DESC' : 'ASC'; ?>
                            <tr>
                                <?php $__currentLoopData = [
                                    'name'             => 'Name / Hersteller',
                                    'baustein'         => 'Baustein',
                                    'sg'               => 'Sachgebiet',
                                    'verantwortlich_sg'=> 'Verantwortlichkeiten',
                                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="<?php echo e(route('applikationen.index', ['sort' => $col, 'order' => $sort === $col ? $nextOrder : 'ASC', 'search' => $search])); ?>"
                                       class="hover:text-gray-800 flex items-center gap-1">
                                        <?php echo e($label); ?>

                                        <?php if($sort === $col): ?> <span><?php echo e($order === 'ASC' ? '↑' : '↓'); ?></span> <?php endif; ?>
                                    </a>
                                </th>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schutzbedarf</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $__empty_1 = true; $__currentLoopData = $apps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $app): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900"><?php echo e($app->name); ?></div>
                                        <?php if($app->hersteller): ?>
                                            <div class="text-xs text-gray-400 mt-0.5"><?php echo e($app->hersteller); ?></div>
                                        <?php endif; ?>
                                        <?php if($app->einsatzzweck): ?>
                                            <div class="text-xs text-gray-500 mt-0.5 line-clamp-1"><?php echo e(Str::limit($app->einsatzzweck, 60)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <?php if($app->baustein): ?>
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-800">
                                                <?php echo e($app->baustein); ?>

                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-300">–</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo e($app->sg ?: '–'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?php if($app->verantwortlich_sg): ?>
                                            <div><?php echo e($app->verantwortlich_sg); ?></div>
                                        <?php endif; ?>
                                        <?php if($app->admin): ?>
                                            <div class="text-xs text-gray-400">Admin: <?php echo e($app->admin); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-1">
                                            <?php $__currentLoopData = ['confidentiality' => 'V', 'integrity' => 'I', 'availability' => 'V']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $prefix): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php $farbe = \App\Models\Applikation::SCHUTZBEDARF_FARBEN[$app->$field] ?? 'bg-gray-100 text-gray-700'; ?>
                                                <span class="px-1.5 py-0.5 text-xs font-bold rounded <?php echo e($farbe); ?>"
                                                      title="<?php echo e(['confidentiality'=>'Vertraulichkeit','integrity'=>'Integrität','availability'=>'Verfügbarkeit'][$field]); ?>: <?php echo e(\App\Models\Applikation::SCHUTZBEDARF[$app->$field] ?? $app->$field); ?>">
                                                    <?php echo e($app->$field); ?>

                                                </span>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium" x-data="{ showDelete: false }">
                                        <a href="<?php echo e(route('applikationen.edit', $app)); ?>"
                                           class="text-indigo-600 hover:text-indigo-900 mr-3">Bearbeiten</a>
                                        <button @click="showDelete = true" type="button"
                                                class="text-red-600 hover:text-red-900">Löschen</button>

                                        <div x-show="showDelete" x-cloak @click.away="showDelete = false"
                                             class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                                            <div class="flex items-center justify-center min-h-screen px-4">
                                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Applikation löschen</h3>
                                                    <p class="text-sm text-gray-500 mb-4">Soll <strong><?php echo e($app->name); ?></strong> wirklich gelöscht werden?</p>
                                                    <div class="flex justify-end gap-3">
                                                        <button @click="showDelete = false" type="button"
                                                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Abbrechen</button>
                                                        <form action="<?php echo e(route('applikationen.destroy', $app)); ?>" method="POST" class="inline">
                                                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Löschen</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">Keine Applikationen gefunden.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if($apps->hasPages()): ?>
                <div class="mt-4"><?php echo e($apps->links()); ?></div>
            <?php endif; ?>

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
<?php /**PATH C:\xampp\htdocs\itcockpit\new\resources\views/applikationen/index.blade.php ENDPATH**/ ?>