<x-filament-panels::page>
<div x-data="{
    crons:[], loading:false,
    form:{schedule:'* * * * *',command:''},
    toast:{show:false,msg:'',ok:true},
    presets:[
        {label:'Chaque minute',val:'* * * * *'},
        {label:'Chaque heure',val:'0 * * * *'},
        {label:'Chaque jour à minuit',val:'0 0 * * *'},
        {label:'Chaque semaine',val:'0 0 * * 0'},
        {label:'Chaque mois',val:'0 0 1 * *'},
    ],
    init(){this.load();},
    async get(url){const r=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});return r.json();},
    async post(url,body){const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(body)});return r.json();},
    async load(){this.loading=true;this.crons=await this.get('/cockpit/crons');this.loading=false;},
    async add(){if(!this.form.command)return;const d=await this.post('/cockpit/crons',{schedule:this.form.schedule,command:this.form.command});this.notify(d.ok?'Cron ajouté':d.error,d.ok);this.form.command='';this.load();},
    async remove(raw){if(!confirm('Supprimer ce cron ?'))return;const r=await fetch('/cockpit/crons',{method:'DELETE',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content},body:JSON.stringify({raw})});const d=await r.json();this.notify(d.ok?'Supprimé':d.error,d.ok);this.load();},
    notify(msg,ok=true){this.toast={show:true,msg,ok};setTimeout(()=>this.toast.show=false,3000);}
}" x-init="init()" class="space-y-6">

    <div class="p-4 rounded-lg space-y-4" style="background:var(--c-surface);border:1px solid var(--c-border)">
        <div class="text-sm font-semibold" style="color:var(--c-text)">Ajouter un cron</div>
        <div class="flex gap-2 flex-wrap">
            <template x-for="p in presets" :key="p.val">
                <button @click="form.schedule=p.val" class="px-2.5 py-1 text-xs rounded font-mono"
                    :style="form.schedule===p.val?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-surface2);color:var(--c-text3);border:1px solid var(--c-border)'"
                    x-text="p.label"></button>
            </template>
        </div>
        <div class="flex gap-3 flex-wrap">
            <input x-model="form.schedule" placeholder="* * * * *" class="w-48 text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-accent)">
            <input x-model="form.command" placeholder="php /var/www/pelican/artisan schedule:run" class="flex-1 text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
            <button @click="add()" class="px-4 py-1.5 text-sm rounded font-medium" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)">+ Ajouter</button>
        </div>
        <div class="text-xs font-mono" style="color:var(--c-text3)">
            Aperçu : <span x-text="form.schedule+' '+form.command" style="color:var(--c-text2)"></span>
        </div>
    </div>

    <div x-show="loading" class="text-center py-8 text-sm" style="color:var(--c-text3)">Chargement…</div>

    <div x-show="!loading" class="rounded-lg overflow-hidden" style="border:1px solid var(--c-border)">
        <table class="w-full text-sm">
            <thead><tr style="background:var(--c-surface2);border-bottom:1px solid var(--c-border)">
                <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)" width="180">Schedule</th>
                <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Commande</th>
                <th width="80"></th>
            </tr></thead>
            <tbody>
                <template x-for="c in crons" :key="c.raw">
                    <tr style="border-bottom:1px solid var(--c-border);background:var(--c-surface)">
                        <td class="px-3 py-2.5 font-mono text-xs" style="color:var(--c-accent)" x-text="c.schedule"></td>
                        <td class="px-3 py-2.5 font-mono text-xs" style="color:var(--c-text)" x-text="c.command"></td>
                        <td class="px-3 py-2.5"><button @click="remove(c.raw)" class="px-2 py-1 text-xs rounded" style="background:var(--c-danger-dim);color:var(--c-danger)">✕</button></td>
                    </tr>
                </template>
                <tr x-show="crons.length===0"><td colspan="3" class="text-center py-8 text-sm" style="color:var(--c-text3)">Aucun cron configuré</td></tr>
            </tbody>
        </table>
    </div>

    <div x-show="toast.show" x-transition class="fixed bottom-16 right-6 z-50 px-4 py-2 rounded-lg text-sm shadow-lg"
         :style="toast.ok?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-danger-dim);color:var(--c-danger)'"
         x-text="toast.msg"></div>
</div>
</x-filament-panels::page>
