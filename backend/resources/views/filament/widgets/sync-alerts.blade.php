<x-filament-widgets::widget>
    <x-filament::section
        heading="Проблеми със синхронизацията"
        icon="heroicon-o-exclamation-triangle"
        icon-color="danger"
    >
        <ul style="display: grid; gap: 0.5rem;">
            @foreach ($this->getAlerts() as $alert)
                <li>
                    <strong>{{ $alert['theatre']->name }}</strong>:
                    {{ implode(' ', $alert['reasons']) }}
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
