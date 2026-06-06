<x-filament-panels::page>
<div x-data="{
    services:[], filtered:[], search:'', filter:'all', loading:false,
    logsModal:{open:false,service:'',content:''},
    toast:{show:false,msg:'',ok:true},
    init(){this.load();},
    async get(url){const r=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});return r.json();},
    async post(url,body){const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(body)});return r.json();},
    async load(){this.loading=true;this.services=await this.get('/cockpit/systemd/services');this.applyFilter();this.loading=false;},
    applyFilter(){let i=this.services;if(this.filter!=='all')i=i.filter(s=>s.state===this.filter||s.active===this.filter);if(this.search){const q=this.search.toLowerCase();i=i.filter(s=>s.name.toLowerCase().includes(q)||s.description.toLowerCase().includes(q));}this.filtered=i;},
    async action(name,action){const d=await this.post('/cockpit/systemd/services/'+name+'/action',{action});this.notify(name+': '+action+' → '+(d.status||'OK'),!d.error);setTimeout(()=>this.load(),1000);},
    async viewLogs(name){const d=await this.get('/cockpit/systemd/services/'+name+'/logs');this.logsModal={open:true,service:name,content:d.logs||'Aucun log'};},
    notify(msg,ok=true){this.toast={show:true,msg,ok};setTimeout(()=>this.toast.show=false,3000);},
    stateColor(s){return s==='running'?'var(--c-accent)':s==='active'?'var(--c-blue)':s==='failed'?'var(--c-danger)':'var(--c-text3)';},
    stateStyle(s){return s==='running'?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':s==='active'?'background:var(--c-blue-dim);color:var(--c-blue)':s==='failed'?'background:var(--c-danger-dim);color:var(--c-danger)':'background:var(--c-surface2);color:var(--c-text3)';}
}" x-init="init()" class="space-y-5">

    <div class="flex gap-3 flex-wrap">
        <input x-model="search" @input="applyFilter()" placeholder="Filtrer…" class="flex-1 min-w-48 text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
        <template x-for="f in ['all','running','active','failed']" :key="f">
            <button @click="filter=f;applyFilter()" class="px-3 py-1.5 text-xs rounded font-medium capitalize"
                :style="filter===f?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-surface2);color:var(--c-text3);border:1px solid var(--c-border)'"
                x-text="f"></button>
        </template>
        <button @click="load()" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text2)">↺</button>
    </div>

    <div x-show="loading" class="text-center py-8 text-sm" style="color:var(--c-text3)">Chargement…</div>

    <div x-show="!loading" class="rounded-lg overflow-hidden" style="border:1px solid var(--c-border)">
        <table class="w-full text-sm">
            <thead><tr style="background:var(--c-surface2);border-bottom:1px solid var(--c-border)">
                <th class="text-left px-3 py-2 text-xs uppercase tracking-wider font-semibold" style="color:var(--c-text3)" width="100">Statut</th>
                <th class="text-left px-3 py-2 text-xs uppercase tracking-wider font-semibold" style="color:var(--c-text3)">Service</th>
                <th class="text-left px-3 py-2 text-xs uppercase tracking-wider font-semibold" style="color:var(--c-text3)">Description</th>
                <th class="text-left px-3 py-2 text-xs uppercase tracking-wider font-semibold" style="color:var(--c-text3)" width="200">Actions</th>
            </tr></thead>
            <tbody>
                <template x-for="s in filtered" :key="s.unit">
                    <tr style="border-bottom:1px solid var(--c-border);background:var(--c-surface)">
                        <td class="px-3 py-2.5"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold" :style="stateStyle(s.state)"><span class="w-1.5 h-1.5 rounded-full" :style="'background:'+stateColor(s.state)"></span><span x-text="s.sub||s.active"></span></span></td>
                        <td class="px-3 py-2.5"><div class="font-mono text-xs font-semibold" style="color:var(--c-text)" x-text="s.name"></div><div class="font-mono text-xs" style="color:var(--c-text3)" x-text="s.unit"></div></td>
                        <td class="px-3 py-2.5 text-xs" style="color:var(--c-text2)" x-text="s.description"></td>
                        <td class="px-3 py-2.5"><div class="flex gap-1 flex-wrap">
                            <template x-if="s.state!=='running'&&s.state!=='active'"><button @click="action(s.name,'start')" class="px-2 py-1 text-xs rounded" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)">▶ Start</button></template>
                            <template x-if="s.state==='running'||s.state==='active'"><button @click="action(s.name,'stop')" class="px-2 py-1 text-xs rounded" style="background:var(--c-danger-dim);color:var(--c-danger)">■ Stop</button></template>
                            <button @click="action(s.name,'restart')" class="px-2 py-1 text-xs rounded" style="background:var(--c-surface2);color:var(--c-text2);border:1px solid var(--c-border)">↺</button>
                            <button @click="viewLogs(s.name)" class="px-2 py-1 text-xs rounded" style="background:var(--c-blue-dim);color:var(--c-blue)">Logs</button>
                        </div></td>
                    </tr>
                </template>
                <tr x-show="filtered.length===0"><td colspan="4" class="text-center py-8 text-sm" style="color:var(--c-text3)">Aucun service</td></tr>
            </tbody>
        </table>
    </div>

    <div x-show="logsModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.75)">
        <div class="w-full max-w-4xl rounded-xl flex flex-col" style="background:var(--c-surface);border:1px solid var(--c-border2);max-height:80vh">
            <div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid var(--c-border)">
                <span class="font-mono text-sm font-semibold" style="color:var(--c-accent)" x-text="logsModal.service+'.service'"></span>
                <button @click="logsModal.open=false" style="color:var(--c-text3)">✕</button>
            </div>
            <pre class="flex-1 overflow-auto p-4 text-xs font-mono whitespace-pre-wrap" style="color:#ccc;background:var(--c-bg)" x-text="logsModal.content"></pre>
        </div>
    </div>
    <div x-show="toast.show" x-transition class="fixed bottom-16 right-6 z-50 px-4 py-2 rounded-lg text-sm shadow-lg" :style="toast.ok?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-danger-dim);color:var(--c-danger)'" x-text="toast.msg"></div>
</div>
</x-filament-panels::page>
