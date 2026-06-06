<x-filament-panels::page>
<div x-data="{
    status:{}, rules:[], loading:false,
    form:{port:'',proto:'tcp',action:'allow'},
    toast:{show:false,msg:'',ok:true},
    init(){this.load();},
    async get(url){const r=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});return r.json();},
    async post(url,body){const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(body)});return r.json();},
    async load(){this.loading=true;this.status=await this.get('/cockpit/firewall/status');this.parseRules();this.loading=false;},
    parseRules(){
        const lines=(this.status.numbered||'').split('\n');
        this.rules=[];
        lines.forEach(l=>{const m=l.match(/^\[\s*(\d+)\]\s+(.+)$/);if(m)this.rules.push({num:parseInt(m[1]),rule:m[2].trim()});});
    },
    async addRule(){if(!this.form.port)return;const url='/cockpit/firewall/'+(this.form.action==='allow'?'allow':'deny');const d=await this.post(url,{port:this.form.port,proto:this.form.proto});this.notify(d.output||d.error,!d.error);this.load();},
    async deleteRule(num){if(!confirm('Supprimer règle #'+num+' ?'))return;const r=await fetch('/cockpit/firewall/rule',{method:'DELETE',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content},body:JSON.stringify({num})});const d=await r.json();this.notify(d.output||d.error,!d.error);this.load();},
    async toggle(){const action=this.status.active?'disable':'enable';const d=await this.post('/cockpit/firewall/toggle',{action});this.notify(d.output,true);this.load();},
    notify(msg,ok=true){this.toast={show:true,msg,ok};setTimeout(()=>this.toast.show=false,3000);}
}" x-init="init()" class="space-y-6">

    <div class="flex items-center gap-4 p-4 rounded-lg" style="background:var(--c-surface);border:1px solid var(--c-border)">
        <div>
            <div class="text-sm font-semibold" style="color:var(--c-text)">Statut UFW</div>
            <div class="text-xs mt-1" :style="status.active?'color:var(--c-accent)':'color:var(--c-danger)'" x-text="status.active?'● Actif':'● Inactif'"></div>
        </div>
        <button @click="toggle()" class="px-4 py-2 text-sm rounded font-medium"
            :style="status.active?'background:var(--c-danger-dim);color:var(--c-danger);border:1px solid var(--c-danger)':'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)'"
            x-text="status.active?'Désactiver UFW':'Activer UFW'"></button>
        <button @click="load()" class="px-3 py-2 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text2)">↺</button>
    </div>

    <div class="p-4 rounded-lg space-y-3" style="background:var(--c-surface);border:1px solid var(--c-border)">
        <div class="text-sm font-semibold" style="color:var(--c-text)">Ajouter une règle</div>
        <div class="flex gap-3 flex-wrap">
            <input x-model="form.port" placeholder="Port (ex: 80 ou 8000:9000)" class="flex-1 text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
            <select x-model="form.proto" class="text-sm px-3 py-1.5 rounded" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
                <option value="tcp">TCP</option><option value="udp">UDP</option><option value="any">Any</option>
            </select>
            <select x-model="form.action" class="text-sm px-3 py-1.5 rounded" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
                <option value="allow">Allow</option><option value="deny">Deny</option>
            </select>
            <button @click="addRule()" class="px-4 py-1.5 text-sm rounded font-medium" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)">+ Ajouter</button>
        </div>
    </div>

    <div class="rounded-lg overflow-hidden" style="border:1px solid var(--c-border)">
        <table class="w-full text-sm">
            <thead><tr style="background:var(--c-surface2);border-bottom:1px solid var(--c-border)">
                <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)" width="60">#</th>
                <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Règle</th>
                <th width="80"></th>
            </tr></thead>
            <tbody>
                <template x-for="rule in rules" :key="rule.num">
                    <tr style="border-bottom:1px solid var(--c-border);background:var(--c-surface)">
                        <td class="px-3 py-2.5 font-mono text-xs" style="color:var(--c-text3)" x-text="rule.num"></td>
                        <td class="px-3 py-2.5 font-mono text-xs" style="color:var(--c-text)" x-text="rule.rule"></td>
                        <td class="px-3 py-2.5"><button @click="deleteRule(rule.num)" class="px-2 py-1 text-xs rounded" style="background:var(--c-danger-dim);color:var(--c-danger)">✕</button></td>
                    </tr>
                </template>
                <tr x-show="rules.length===0"><td colspan="3" class="text-center py-6 text-sm" style="color:var(--c-text3)">Aucune règle</td></tr>
            </tbody>
        </table>
    </div>

    <div class="rounded-lg p-4" style="background:var(--c-surface);border:1px solid var(--c-border)">
        <div class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--c-text3)">Statut complet</div>
        <pre class="text-xs font-mono whitespace-pre-wrap" style="color:#aaa" x-text="status.status"></pre>
    </div>

    <div x-show="toast.show" x-transition class="fixed bottom-16 right-6 z-50 px-4 py-2 rounded-lg text-sm shadow-lg"
         :style="toast.ok?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-danger-dim);color:var(--c-danger)'"
         x-text="toast.msg"></div>
</div>
</x-filament-panels::page>
