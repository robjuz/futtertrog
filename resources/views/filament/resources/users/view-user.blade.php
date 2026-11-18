<x-filament-panels::page>
    <x-filament::section heading="Konto und Guthaben">
        <p>{{ $user->name }} · {{ $user->email }}</p>
        <p>Guthaben: {{ $balance }}</p>
    </x-filament::section>

    <x-filament::section heading="Bestellverlauf">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr><th>Gericht</th><th>Datum</th><th>Menge</th><th>Preis</th><th>Summe</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($orders as $item)
                    <tr>
                        <td>{{ $item->meal->title }}</td><td>{{ $item->meal->date->format('d.m.Y') }}</td>
                        <td>{{ $item->quantity }}</td><td>{{ $item->meal->price }}</td><td>{{ $item->subtotal }}</td>
                        <td>{{ $item->order->status === \App\Models\Order::STATUS_ORDERED ? 'Abgeschlossen / bestellt' : 'Offen' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Keine Bestellungen vorhanden.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    </x-filament::section>

    <x-filament::section heading="Buchungsverlauf">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr><th>Datum</th><th>Betrag</th><th>Kommentar</th></tr></thead>
                <tbody>
                @forelse ($deposits as $deposit)
                    <tr><td>{{ $deposit->created_at->format('d.m.Y H:i') }}</td><td>@money($deposit->value)</td><td>{{ $deposit->comment }}</td></tr>
                @empty
                    <tr><td colspan="3">Keine Buchungen vorhanden.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $deposits->links() }}
    </x-filament::section>
</x-filament-panels::page>
