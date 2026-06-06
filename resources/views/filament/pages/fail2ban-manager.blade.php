<x-filament-panels::page>
<div x-data="{
    status:{}, jails:[], selectedJail:'sshd', jailData:{banned:[]},
    banForm:{ip:'',jail:'sshd'},
    toast:{show:false,msg:'',ok:true},
    init(){this.load();},
    async get(url){const r=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});return r.json();},
    async post(url,body){const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(body)});return r.json();},
    async load(){this.status=await this.get('/cockpit/fail2ban/status');this.parseJails();this.loadJail();},
    parseJails(){const m=(this.status.status||'').match(/Jail list:\s*(.+)/);if(m)this.jails=m[1].split(',').map(j=>j.trim()).filter(Boolean);},
    async loadJail(){this.jailData=await this.get('/cockpit/fail2ban/jail/'+this.selectedJail);},
    async unban(ip){const d=await this.post('/cockpit/fail2ban/unban',{ip,jail:this.selectedJail});this.notify(d.output||d.error,!d.error);setTimeout(()=>this.loadJail(),500);},
    async ban(){if(!this.banForm.ip)return;const d=await this.post('/cockpit/fail2ban/ban',{ip:this.banForm.ip,jail:this.banForm.jail});this.notify(d.output||d.error,!d.error);this.banForm.ip='';setTimeout(()=>this.loadJail(),500);},
    notify(msg,ok=true){this.toast={show:true,msg,ok};setTimeout(()=>this.toast.show=false,3000);}
}" x-init="init()" class="space-y-6">

    <div class="flex items-center gap-4 p-4 rounded-lg" style="background:var(--c-surface);border:1px solid var(--c-border)">
        <div>
            <div class="text-sm font-semibold" style="color:var(--c-text)">Fail2ban</div>
            <div class="text-xs mt-1" :style="status.active?'color:var(--c-accent)':'color:var(--c-danger)'" x-text="status.active?'● En cours':'● Arrêté'"></div>
        </div>
        <div class="flex gap-2">
            <template x-for="jail in jails" :key="jail">
                <button @click="selectedJail=jail;loadJail()" class="px-3 py-1 text-xs rounded font-mono"
                    :style="selectedJail===jail?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-surface2);color:var(--c-text3);border:1px solid var(--c-border)'"
                    x-text="jail"></button>
            </template>
        </div>
        <button @click="load()" class="px-3 py-2 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text2)">↺</button>
    </div>

    <div class="p-4 rounded-lg space-y-3" style="background:var(--c-surface);border:1px solid var(--c-border)">
        <div class="text-sm font-semibold" style="color:var(--c-text)">Bannir une IP</div>
        <div class="flex gap-3">
            <input x-model="banForm.ip" placeholder="192.168.1.1" class="flex-1 text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
            <select x-model="banForm.jail" class="text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
                <template x-for="jail in jails" :key="jail"><option :value="jail" x-text="jail"></option></template>
            </select>
            <button @click="ban()" class="px-4 py-1.5 text-sm rounded font-medium" style="background:var(--c-danger-dim);color:var(--c-danger);border:1px solid var(--c-danger)">🚫 Bannir</button>
        </div>
    </div>

    <div class="rounded-lg overflow-hidden" style="border:1px solid var(--c-border)">
        <div class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider" style="background:var(--c-surface2);color:var(--c-text3);border-bottom:1px solid var(--c-border)">
            IPs bannies — <span x-text="selectedJail" style="color:var(--c-accent)"></span>
            (<span x-text="jailData.banned?.length||0"></span>)
        </div>
        <div x-show="!jailData.banned?.length" class="text-center py-6 text-sm" style="color:var(--c-text3)">Aucune IP bannie</div>
        <template x-for="ip in (jailData.banned||[])" :key="ip">
            <div class="flex items-center justify-between px-4 py-2.5" style="border-bottom:1px solid var(--c-border);background:var(--c-surface)">
                <span class="font-mono text-sm" style="color:var(--c-danger)" x-text="ip"></span>
                <button @click="unban(ip)" class="px-3 py-1 text-xs rounded" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)">✓ Débannir</button>
            </div>
        </template>
    </div>

    <div class="rounded-lg p-4" style="background:var(--c-surface);border:1px solid var(--c-border)">
        <div class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--c-text3)">Détails jail</div>
        <pre class="text-xs font-mono whitespace-pre-wrap" style="color:#aaa" x-text="jailData.raw"></pre>
    </div>

    <div x-show="toast.show" x-transition class="fixed bottom-16 right-6 z-50 px-4 py-2 rounded-lg text-sm shadow-lg"
         :style="toast.ok?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-danger-dim);color:var(--c-danger)'"
         x-text="toast.msg"></div>
</div>
</x-filament-panels::page>
