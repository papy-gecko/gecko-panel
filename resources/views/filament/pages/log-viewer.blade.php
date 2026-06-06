<x-filament-panels::page>
<div x-data="{
    logs:[], selected:'journalctl', content:'', loading:false, search:'', lines:100, autoRefresh:false, timer:null,
    init(){this.loadList();this.tail();this.$watch('autoRefresh',v=>{clearInterval(this.timer);if(v)this.timer=setInterval(()=>this.tail(),3000);});},
    async get(url){const r=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});return r.json();},
    async loadList(){this.logs=await this.get('/cockpit/syslog');},
    async tail(){this.loading=true;const d=await this.get('/cockpit/syslog/tail?log='+this.selected+'&lines='+this.lines);this.content=d.content||d.error||'';this.loading=false;this.$nextTick(()=>{const el=this.$refs.out;if(el)el.scrollTop=el.scrollHeight;});},
    async doSearch(){if(!this.search)return this.tail();this.loading=true;const d=await this.get('/cockpit/syslog/search?log='+this.selected+'&query='+encodeURIComponent(this.search));this.content=d.content||'Aucun résultat';this.loading=false;},
    selectLog(name){this.selected=name;this.search='';this.tail();}
}" x-init="init()" class="space-y-4" style="height:calc(100vh - 160px);display:flex;flex-direction:column">

    <div class="flex gap-3 flex-wrap">
        <select x-model="selected" @change="selectLog(selected)" class="text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-surface);border:1px solid var(--c-border2);color:var(--c-text)">
            <template x-for="l in logs" :key="l.name">
                <option :value="l.name" x-text="l.name+' ('+l.size+')'"></option>
            </template>
        </select>
        <select x-model.number="lines" @change="tail()" class="text-sm px-3 py-1.5 rounded" style="background:var(--c-surface);border:1px solid var(--c-border2);color:var(--c-text)">
            <option value="50">50 lignes</option>
            <option value="100" selected>100 lignes</option>
            <option value="200">200 lignes</option>
            <option value="500">500 lignes</option>
        </select>
        <input x-model="search" @keydown.enter="doSearch()" placeholder="Rechercher… (Entrée)" class="flex-1 text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
        <button @click="doSearch()" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-blue-dim);color:var(--c-blue);border:1px solid var(--c-blue)">🔍 Search</button>
        <button @click="tail()" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text2)">↺ Refresh</button>
        <label class="flex items-center gap-1.5 text-xs" style="color:var(--c-text3)"><input type="checkbox" x-model="autoRefresh"> Auto 3s</label>
    </div>

    <div class="flex-1 rounded-lg overflow-hidden flex flex-col" style="background:#050505;border:1px solid var(--c-border2);min-height:0">
        <div class="flex items-center justify-between px-3 py-1.5" style="background:#0a0a0a;border-bottom:1px solid var(--c-border2)">
            <span class="font-mono text-xs" style="color:var(--c-accent)" x-text="selected"></span>
            <span x-show="loading" class="text-xs animate-pulse" style="color:var(--c-text3)">Chargement…</span>
        </div>
        <pre x-ref="out" class="flex-1 overflow-auto p-4 text-xs font-mono leading-relaxed whitespace-pre-wrap" style="color:#ccc" x-text="content"></pre>
    </div>
</div>
</x-filament-panels::page>
