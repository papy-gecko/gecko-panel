<x-filament-panels::page>
<div x-data="{
    processes:[], search:'', sort:'cpu', loading:false, auto:false, timer:null,
    killModal:{open:false,pid:null,cmd:'',signal:'TERM'},
    toast:{show:false,msg:'',ok:true},
    init(){this.load();this.$watch('auto',v=>{clearInterval(this.timer);if(v)this.timer=setInterval(()=>this.load(),3000);});},
    async load(){const p=new URLSearchParams({sort:this.sort,search:this.search});const r=await fetch('/cockpit/processes?'+p,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});this.processes=await r.json();},
    openKill(pid,cmd){this.killModal={open:true,pid,cmd,signal:'TERM'};},
    async confirmKill(){const r=await fetch('/cockpit/processes/kill',{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({pid:this.killModal.pid,signal:this.killModal.signal})});const d=await r.json();this.killModal.open=false;this.notify(d.output||'Signal envoyé',!d.error);setTimeout(()=>this.load(),500);},
    notify(msg,ok=true){this.toast={show:true,msg,ok};setTimeout(()=>this.toast.show=false,3000);},
    cpuColor(p){return p>=50?'var(--c-danger)':p>=20?'var(--c-warn)':p>0?'var(--c-accent)':'var(--c-text3)';}
}" x-init="init()" class="space-y-5">

    <div class="flex gap-3 flex-wrap">
        <input x-model="search" @input="load()" placeholder="Filtrer par commande ou PID…" class="flex-1 min-w-48 text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
        <select x-model="sort" @change="load()" class="text-sm px-3 py-1.5 rounded" style="background:var(--c-surface);border:1px solid var(--c-border2);color:var(--c-text)">
            <option value="cpu">Trier CPU</option><option value="mem">Trier RAM</option><option value="pid">Trier PID</option>
        </select>
        <button @click="load()" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text2)">↺</button>
        <label class="flex items-center gap-2 text-xs" style="color:var(--c-text3)"><input type="checkbox" x-model="auto"> Auto 3s</label>
    </div>

    <div class="rounded-lg overflow-hidden" style="border:1px solid var(--c-border)">
        <table class="w-full text-xs font-mono">
            <thead><tr style="background:var(--c-surface2);border-bottom:1px solid var(--c-border)">
                <th class="text-left px-3 py-2 uppercase tracking-wider font-semibold" style="color:var(--c-text3)" width="55">PID</th>
                <th class="text-left px-3 py-2 uppercase tracking-wider font-semibold" style="color:var(--c-text3)" width="70">User</th>
                <th class="text-left px-3 py-2 uppercase tracking-wider font-semibold" style="color:var(--c-text3)" width="60">CPU%</th>
                <th class="text-left px-3 py-2 uppercase tracking-wider font-semibold" style="color:var(--c-text3)" width="60">RAM%</th>
                <th class="text-left px-3 py-2 uppercase tracking-wider font-semibold" style="color:var(--c-text3)" width="60">VSZ</th>
                <th class="text-left px-3 py-2 uppercase tracking-wider font-semibold" style="color:var(--c-text3)">Commande</th>
                <th width="70"></th>
            </tr></thead>
            <tbody>
                <template x-for="p in processes" :key="p.pid">
                    <tr style="border-bottom:1px solid var(--c-border);background:var(--c-surface)">
                        <td class="px-3 py-1.5" style="color:var(--c-text3)" x-text="p.pid"></td>
                        <td class="px-3 py-1.5" style="color:var(--c-text2)" x-text="p.user"></td>
                        <td class="px-3 py-1.5" :style="'color:'+cpuColor(p.cpu)" x-text="p.cpu.toFixed(1)+'%'"></td>
                        <td class="px-3 py-1.5" :style="'color:'+cpuColor(p.mem)" x-text="p.mem.toFixed(1)+'%'"></td>
                        <td class="px-3 py-1.5" style="color:var(--c-text3)" x-text="p.vsz"></td>
                        <td class="px-3 py-1.5" style="color:var(--c-text);max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="p.command" :title="p.command"></td>
                        <td class="px-3 py-1.5"><button @click="openKill(p.pid,p.command)" class="px-2 py-0.5 text-xs rounded" style="background:var(--c-danger-dim);color:var(--c-danger)">✕ Kill</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div x-show="killModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.7)">
        <div class="rounded-xl p-6 w-full max-w-md" style="background:var(--c-surface);border:1px solid var(--c-border2)">
            <h3 class="font-semibold mb-2" style="color:var(--c-text)">Terminer le processus</h3>
            <p class="text-sm mb-4" style="color:var(--c-text2)">PID <strong x-text="killModal.pid" style="color:var(--c-danger)"></strong> : <span class="font-mono text-xs" x-text="killModal.cmd"></span></p>
            <div class="flex gap-2 mb-4">
                <template x-for="sig in ['TERM','KILL','HUP']" :key="sig">
                    <button @click="killModal.signal=sig" class="flex-1 px-3 py-2 text-xs rounded" :style="killModal.signal===sig?'background:var(--c-danger-dim);color:var(--c-danger);border:1px solid var(--c-danger)':'background:var(--c-surface2);color:var(--c-text3);border:1px solid var(--c-border)'" x-text="sig"></button>
                </template>
            </div>
            <div class="flex gap-2 justify-end">
                <button @click="killModal.open=false" class="px-4 py-2 text-sm rounded" style="background:var(--c-surface2);color:var(--c-text2);border:1px solid var(--c-border)">Annuler</button>
                <button @click="confirmKill()" class="px-4 py-2 text-sm rounded font-semibold" style="background:var(--c-danger-dim);color:var(--c-danger);border:1px solid var(--c-danger)">Envoyer</button>
            </div>
        </div>
    </div>
    <div x-show="toast.show" x-transition class="fixed bottom-16 right-6 z-50 px-4 py-2 rounded-lg text-sm shadow-lg" :style="toast.ok?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-danger-dim);color:var(--c-danger)'" x-text="toast.msg"></div>
</div>
</x-filament-panels::page>
