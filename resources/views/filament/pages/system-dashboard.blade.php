<x-filament-panels::page>
<div x-data="{
    stats: {},
    online: false,
    interval: 5000,
    timer: null,
    init() { this.fetch(); this.timer = setInterval(() => this.fetch(), this.interval); },
    async fetch() {
        try {
            const r = await fetch('/cockpit/system/stats', { headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
            if (!r.ok) throw new Error();
            this.stats = await r.json();
            this.online = true;
        } catch(e) { this.online = false; }
    },
    pct(v) { return Math.min(Math.max(v||0, 0), 100); },
    color(v) { return v>=90?'#cc3333':v>=70?'#cc7700':'#7fdb4f'; }
}" x-init="init()" class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">Système Live</h2>
            <p class="text-xs mt-1 font-mono" style="color:var(--c-text3)">
                Uptime: <span x-text="stats.uptime??'—'" style="color:var(--c-accent)"></span>
                &nbsp;·&nbsp; Load: <span x-text="stats.load?stats.load['1']+' / '+stats.load['5']+' / '+stats.load['15']:'—'"></span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <select x-model.number="interval" @change="clearInterval(timer); timer=setInterval(()=>fetch(),interval)"
                class="text-xs rounded px-2 py-1" style="background:var(--c-surface);border:1px solid var(--c-border2);color:var(--c-text)">
                <option value="2000">2s</option>
                <option value="5000" selected>5s</option>
                <option value="10000">10s</option>
            </select>
            <span class="text-xs" style="color:var(--c-text3)" x-text="online?'● En ligne':'● Hors ligne'"
                  :style="online?'color:#7fdb4f':'color:#cc3333'"></span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <div class="rounded-lg p-5" style="background:var(--c-surface);border:1px solid var(--c-border)">
            <div class="text-xs uppercase tracking-widest font-semibold mb-4" style="color:var(--c-text3)">CPU</div>
            <div class="text-4xl font-mono font-bold mb-1" x-text="stats.cpu ? stats.cpu.percent+'%' : '—'"></div>
            <div class="text-xs mb-3" style="color:var(--c-text3)" x-text="stats.cpu ? stats.cpu.cores+' cœurs' : ''"></div>
            <div class="h-2 rounded-full" style="background:var(--c-border2)">
                <div class="h-2 rounded-full transition-all duration-700"
                     :style="{width: pct(stats.cpu?.percent)+'%', background: color(stats.cpu?.percent)}"></div>
            </div>
        </div>

        <div class="rounded-lg p-5" style="background:var(--c-surface);border:1px solid var(--c-border)">
            <div class="text-xs uppercase tracking-widest font-semibold mb-4" style="color:var(--c-text3)">RAM</div>
            <div class="text-4xl font-mono font-bold mb-1" x-text="stats.ram ? stats.ram.percent+'%' : '—'"></div>
            <div class="text-xs mb-3" style="color:var(--c-text2)" x-text="stats.ram ? stats.ram.used+' / '+stats.ram.total : ''"></div>
            <div class="h-2 rounded-full" style="background:var(--c-border2)">
                <div class="h-2 rounded-full transition-all duration-700"
                     :style="{width: pct(stats.ram?.percent)+'%', background: color(stats.ram?.percent)}"></div>
            </div>
        </div>

        <div class="rounded-lg p-5" style="background:var(--c-surface);border:1px solid var(--c-border)">
            <div class="text-xs uppercase tracking-widest font-semibold mb-4" style="color:var(--c-text3)">Disque</div>
            <div class="text-4xl font-mono font-bold mb-1" x-text="stats.disk ? stats.disk.percent+'%' : '—'"></div>
            <div class="text-xs mb-3" style="color:var(--c-text2)" x-text="stats.disk ? stats.disk.used+' / '+stats.disk.total : ''"></div>
            <div class="h-2 rounded-full" style="background:var(--c-border2)">
                <div class="h-2 rounded-full transition-all duration-700"
                     :style="{width: pct(stats.disk?.percent)+'%', background: color(stats.disk?.percent)}"></div>
            </div>
            <div class="text-xs mt-2 font-mono" style="color:var(--c-text3)">
                Libre: <span x-text="stats.disk?.free??''" style="color:var(--c-accent)"></span>
            </div>
        </div>

        <div class="rounded-lg p-5" style="background:var(--c-surface);border:1px solid var(--c-border)">
            <div class="text-xs uppercase tracking-widest font-semibold mb-4" style="color:var(--c-text3)">
                Réseau <span x-text="stats.net ? '('+stats.net.iface+')':''"></span>
            </div>
            <div class="space-y-3">
                <div>
                    <div class="text-xs mb-1" style="color:var(--c-text3)">↓ Download</div>
                    <div class="text-xl font-mono font-bold" style="color:var(--c-blue)" x-text="stats.net?.rx_rate??'—'"></div>
                    <div class="text-xs font-mono" style="color:var(--c-text3)">Total: <span x-text="stats.net?.rx??''"></span></div>
                </div>
                <div class="h-px" style="background:var(--c-border)"></div>
                <div>
                    <div class="text-xs mb-1" style="color:var(--c-text3)">↑ Upload</div>
                    <div class="text-xl font-mono font-bold" style="color:var(--c-accent)" x-text="stats.net?.tx_rate??'—'"></div>
                    <div class="text-xs font-mono" style="color:var(--c-text3)">Total: <span x-text="stats.net?.tx??''"></span></div>
                </div>
            </div>
        </div>

    </div>
</div>
</x-filament-panels::page>
