<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Kostenstellen</h2>
            @if($isLeiter)
                <button onclick="document.getElementById('modal-create-cc').classList.remove('hidden')"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                    + Neue Kostenstelle
                </button>
            @endif
        </div>
    </x-slot>

    @include('hh::partials.nav')
    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 rounded bg-green-100 px-4 py-3 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded bg-red-100 px-4 py-3 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nummer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            @if($isLeiter)
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Aktionen</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($costCenters as $cc)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ $cc->number }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ $cc->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if($cc->is_active)
                                        <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Aktiv</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-600">Inaktiv</span>
                                    @endif
                                </td>
                                @if($isLeiter)
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <button onclick="openEditCc({{ $cc->id }}, '{{ addslashes($cc->number) }}', '{{ addslashes($cc->name) }}', {{ $cc->is_active ? 'true' : 'false' }})"
                                                class="mr-2 text-blue-600 hover:underline">
                                            Bearbeiten
                                        </button>
                                        <form method="POST" action="{{ route('hh.cost-centers.destroy', $cc) }}" class="inline"
                                              onsubmit="return confirm('Kostenstelle wirklich löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    @if($isLeiter)
        {{-- Create Modal --}}
        <div id="modal-create-cc" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-md rounded bg-white p-6 shadow-lg">
                <h3 class="mb-4 text-lg font-semibold">Neue Kostenstelle</h3>
                <form method="POST" action="{{ route('hh.cost-centers.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nummer</label>
                        <input type="text" name="number" required
                               class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" required
                               class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="mb-4 flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked id="create-is-active"
                               class="h-4 w-4 rounded border-gray-300 text-blue-600">
                        <label for="create-is-active" class="ml-2 text-sm text-gray-700">Aktiv</label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button"
                                onclick="document.getElementById('modal-create-cc').classList.add('hidden')"
                                class="px-4 py-2 text-sm text-gray-700 hover:underline">
                            Abbrechen
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                            Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Modal --}}
        <div id="modal-edit-cc" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-md rounded bg-white p-6 shadow-lg">
                <h3 class="mb-4 text-lg font-semibold">Kostenstelle bearbeiten</h3>
                <form id="form-edit-cc" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nummer</label>
                        <input type="text" name="number" id="edit-cc-number" required
                               class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="edit-cc-name" required
                               class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="mb-4 flex items-center">
                        <input type="checkbox" name="is_active" value="1" id="edit-cc-is-active"
                               class="h-4 w-4 rounded border-gray-300 text-blue-600">
                        <label for="edit-cc-is-active" class="ml-2 text-sm text-gray-700">Aktiv</label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button"
                                onclick="document.getElementById('modal-edit-cc').classList.add('hidden')"
                                class="px-4 py-2 text-sm text-gray-700 hover:underline">
                            Abbrechen
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                            Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEditCc(id, number, name, isActive) {
                document.getElementById('edit-cc-number').value = number;
                document.getElementById('edit-cc-name').value = name;
                document.getElementById('edit-cc-is-active').checked = isActive;
                document.getElementById('form-edit-cc').action = '{{ url('hh/cost-centers') }}/' + id;
                document.getElementById('modal-edit-cc').classList.remove('hidden');
            }
        </script>
    @endif

</x-app-layout>
