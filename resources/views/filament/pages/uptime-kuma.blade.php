<x-filament-panels::page>
    @php $monitors = $this->getMonitors(); @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($monitors as $monitor)
            @php
                $isUp = $monitor->status === 'up';
                $uptime = $monitor->uptimePercent();
                $logs = $monitor->logs->reverse()->values();
            @endphp
            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full {{ $isUp ? 'bg-green-400' : 'bg-red-500' }} animate-pulse"></span>
                        <span class="text-white font-semibold">{{ $monitor->name }}</span>
                    </div>
                    <span class="text-xs {{ $isUp ? 'text-green-400' : 'text-red-400' }} font-bold">
                        {{ $isUp ? 'EN LIGNE' : 'HORS LIGNE' }}
                    </span>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-400 mb-3">
                    <span>{{ strtoupper($monitor->type) }} — {{ $monitor->target }}{{ $monitor->port ? ':'.$monitor->port : '' }}</span>
                    <span>{{ $monitor->latency ?? '—' }}ms</span>
                </div>

                <div class="flex gap-0.5 mb-2">
                    @foreach($logs->take(40) as $log)
                        <div class="h-6 flex-1 rounded-sm {{ $log->status === 'up' ? 'bg-green-500' : 'bg-red-500' }}" title="{{ $log->checked_at }}"></div>
                    @endforeach
                </div>

                <div class="flex justify-between text-xs text-gray-400">
                    <span>Uptime 24h</span>
                    <span class="{{ $uptime >= 99 ? 'text-green-400' : ($uptime >= 90 ? 'text-yellow-400' : 'text-red-400') }}">{{ $uptime }}%</span>
                </div>

                @if($monitor->last_checked_at)
                    <div class="text-xs text-gray-500 mt-1">Vérifié {{ $monitor->last_checked_at->diffForHumans() }}</div>
                @endif
            </div>
        @empty
            <div class="col-span-3 text-center text-gray-400 py-12">
                <p class="text-lg">Aucune sonde configurée</p>
                <p class="text-sm mt-1">Ajoutez votre première sonde pour commencer le monitoring</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
