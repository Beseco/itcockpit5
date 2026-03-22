<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bestellung bearbeiten
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('orders.update', $order) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('orders._form', ['statusLabels' => $statusLabels])

                        <div class="flex items-center gap-4 mt-6">
                            <x-primary-button>Änderungen speichern</x-primary-button>
                            <a href="{{ route('orders.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                Abbrechen
                            </a>
                        </div>
                    </form>

                    {{-- Status-Verlauf --}}
                    @if ($order->history->isNotEmpty())
                        <div class="mt-8 border-t pt-6">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Status-Verlauf</h4>
                            <ul class="space-y-2">
                                @foreach ($order->history()->orderBy('created_at', 'desc')->get() as $log)
                                    <li class="text-xs text-gray-500">
                                        <span class="font-medium text-gray-700">{{ $log->created_at->format('d.m.Y H:i') }}</span>
                                        — {{ $log->changed_by }} hat Status von
                                        <span class="font-medium">{{ $log->old_value }}</span> auf
                                        <span class="font-medium text-indigo-600">{{ $log->new_value }}</span> geändert
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
