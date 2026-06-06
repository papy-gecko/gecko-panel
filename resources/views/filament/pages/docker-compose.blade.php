<x-filament-panels::page>
<div x-data="dockerCompose()" x-init="init()" class="space-y-5">

    <div class="flex gap-3 flex-wrap items-center">
        <select x-model="selected" @change="loadFile(selected)" class="flex-1 text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-surface);border:1px solid var(--c-border2);color:var(--c-text)">
            <template x-for="f in files" :key="f"><option :value="f" x-text="f"></option></template>
        </select>
        <button @click="scan()" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text2)">🔍 Scanner</button>
    </div>

    <div x-show="selected" class="flex gap-2 flex-wrap">
        <button @click="runCmd('up -d')" :disabled="running" class="px-3 py-1.5 text-xs rounded font-medium" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)">▶ Up -d</button>
        <button @click="runCmd('down')" :disabled="running" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-danger-dim);color:var(--c-danger);border:1px solid var(--c-danger)">■ Down</button>
        <button @click="runCmd('restart')" :disabled="running" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-surface2);color:var(--c-text2);border:1px solid var(--c-border)">↺ Restart</button>
        <button @click="runCmd('ps')" :disabled="running" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-blue-dim);color:var(--c-blue)">📋 PS</button>
        <button @click="runCmd('logs --tail=50')" :disabled="running" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-blue-dim);color:var(--c-blue)">📜 Logs</button>
        <button @click="runCmd('pull')" :disabled="running" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-surface2);color:var(--c-text2);border:1px solid var(--c-border)">↓ Pull</button>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg overflow-hidden flex flex-col" style="border:1px solid var(--c-border);height:400px">
            <div class="flex items-center justify-between px-3 py-2" style="background:var(--c-surface2);border-bottom:1px solid var(--c-border)">
                <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--c-text3)">docker-compose.yml</span>
                <button @click="save()" :disabled="saving" class="px-3 py-1 text-xs rounded" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)" x-text="saving ? '…' : '💾 Save'"></button>
            </div>
            <textarea x-model="content" class="flex-1 p-3 font-mono text-xs resize-none outline-none" style="background:#080808;color:#d4d4d4;tab-size:2" spellcheck="false"></textarea>
        </div>
        <div class="rounded-lg overflow-hidden flex flex-col" style="border:1px solid var(--c-border);height:400px">
            <div class="px-3 py-2 flex items-center justify-between" style="background:var(--c-surface2);border-bottom:1px solid var(--c-border)">
                <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--c-text3)">Output</span>
                <span x-show="running" class="text-xs animate-pulse" style="color:var(--c-accent)">Exécution…</span>
            </div>
            <pre class="flex-1 overflow-auto p-3 text-xs font-mono whitespace-pre-wrap" style="color:#ccc;background:#050505" x-text="output || 'Lancez une commande…'"></pre>
        </div>
    </div>

    <div x-show="toast.show" x-transition class="fixed bottom-16 right-6 z-50 px-4 py-2 rounded-lg text-sm shadow-lg"
         :style="toast.ok ? 'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)' : 'background:var(--c-danger-dim);color:var(--c-danger)'"
         x-text="toast.msg"></div>
</div>

<script>
function dockerCompose() {
    return {
        files: [], selected: '', content: '', loading: false, saving: false, running: false, output: '',
        toast: {show: false, msg: '', ok: true},

        init() { this.scan(); },

        async get(url) {
            const r = await fetch(url, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            return r.json();
        },
        async post(url, body) {
            const r = await fetch(url, {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'X-Requested-With': 'XMLHttpRequest'}, body: JSON.stringify(body)});
            return r.json();
        },

        async scan() {
            this.loading = true;
            const cmd = 'find /var/www /home /opt /srv /root -name docker-compose.yml -o -name docker-compose.yaml 2>/dev/null | head -20';
            const out = await this.post('/cockpit/terminal/execute', {command: cmd, cwd: '/'});
            this.files = (out.output || '').split('\n').filter(f => f.trim());
            if (this.files.length > 0) this.loadFile(this.files[0]);
            else this.loading = false;
        },

        async loadFile(path) {
            this.selected = path;
            this.loading = true;
            const d = await this.get('/cockpit/files/read?path=' + encodeURIComponent(path));
            this.content = d.content || d.error || '';
            this.loading = false;
        },

        async save() {
            this.saving = true;
            const d = await this.post('/cockpit/files/write', {path: this.selected, content: this.content});
            this.saving = false;
            this.notify(d.ok ? 'Sauvegardé' : d.error, d.ok);
        },

        async runCmd(cmd) {
            this.running = true;
            const dir = this.selected.split('/').slice(0, -1).join('/');
            const d = await this.post('/cockpit/terminal/execute', {command: 'cd ' + dir + ' && docker compose ' + cmd + ' 2>&1', cwd: dir});
            this.output = d.output || '';
            this.running = false;
        },

        notify(msg, ok = true) {
            this.toast = {show: true, msg, ok};
            setTimeout(() => this.toast.show = false, 3000);
        }
    }
}
</script>
</x-filament-panels::page>
