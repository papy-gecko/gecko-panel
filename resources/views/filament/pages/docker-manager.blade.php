<x-filament-panels::page>
<div x-data="{
    tab:'containers', items:[], filtered:[], search:'', loading:false,
    pullImage:'', pulling:false,
    logsModal:{open:false,name:'',content:''},
    toast:{show:false,msg:'',ok:true},
    init(){this.loadContainers();},
    async get(url){const r=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});return r.json();},
    async post(url,body){const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(body)});return r.json();},
    setTab(t){this.tab=t;this.items=[];this.filtered=[];const m={containers:'loadContainers',images:'loadImages',volumes:'loadVolumes',networks:'loadNetworks'};this[m[t]]();},
    filter(){const q=this.search.toLowerCase();this.filtered=q?this.items.filter(i=>JSON.stringify(i).toLowerCase().includes(q)):[...this.items];},
    async loadContainers(){this.loading=true;this.items=await this.get('/cockpit/docker/containers');this.filter();this.loading=false;},
    async loadImages(){this.loading=true;this.items=await this.get('/cockpit/docker/images');this.filter();this.loading=false;},
    async loadVolumes(){this.loading=true;this.items=await this.get('/cockpit/docker/volumes');this.filter();this.loading=false;},
    async loadNetworks(){this.loading=true;this.items=await this.get('/cockpit/docker/networks');this.filter();this.loading=false;},
    async action(id,a){await this.post('/cockpit/docker/containers/'+id+'/action',{action:a});this.notify(a+' OK');setTimeout(()=>this.loadContainers(),800);},
    async remove(id){if(!confirm('Supprimer ?'))return;await fetch('/cockpit/docker/containers/'+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content}});this.notify('Supprimé');this.loadContainers();},
    async logs(id,name){const d=await this.get('/cockpit/docker/containers/'+id+'/logs');this.logsModal={open:true,name,content:d.logs||'Aucun log'};},
    async pull(){if(!this.pullImage)return;this.pulling=true;await this.post('/cockpit/docker/images/pull',{image:this.pullImage});this.pulling=false;this.notify('Pull terminé');this.loadImages();},
    async removeImg(id){if(!confirm('Supprimer ?'))return;await fetch('/cockpit/docker/images/'+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content}});this.notify('Supprimé');this.loadImages();},
    notify(msg,ok=true){this.toast={show:true,msg,ok};setTimeout(()=>this.toast.show=false,3000);}
}" x-init="init()" class="space-y-5">

    <div class="flex gap-1 border-b" style="border-color:var(--c-border)">
        <button @click="setTab('containers')" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px" :style="tab==='containers'?'border-color:var(--c-accent);color:var(--c-text)':'border-color:transparent;color:var(--c-text3)'">Containers</button>
        <button @click="setTab('images')" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px" :style="tab==='images'?'border-color:var(--c-accent);color:var(--c-text)':'border-color:transparent;color:var(--c-text3)'">Images</button>
        <button @click="setTab('volumes')" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px" :style="tab==='volumes'?'border-color:var(--c-accent);color:var(--c-text)':'border-color:transparent;color:var(--c-text3)'">Volumes</button>
        <button @click="setTab('networks')" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px" :style="tab==='networks'?'border-color:var(--c-accent);color:var(--c-text)':'border-color:transparent;color:var(--c-text3)'">Réseaux</button>
    </div>

    <div class="flex gap-3">
        <input x-model="search" @input="filter()" placeholder="Filtrer…" class="flex-1 text-sm px-3 py-1.5 rounded font-mono" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
        <button @click="setTab(tab)" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text2)">↺ Refresh</button>
        <template x-if="tab==='images'">
            <div class="flex gap-2">
                <input x-model="pullImage" placeholder="nginx:latest" class="text-sm px-3 py-1.5 rounded font-mono w-40" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
                <button @click="pull()" :disabled="pulling" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-accent-dim);border:1px solid var(--c-accent-border);color:var(--c-accent)" x-text="pulling?'Pull…':'↓ Pull'"></button>
            </div>
        </template>
    </div>

    <div x-show="loading" class="text-center py-8 text-sm" style="color:var(--c-text3)">Chargement…</div>

    <template x-if="!loading && tab==='containers'">
        <div class="rounded-lg overflow-hidden" style="border:1px solid var(--c-border)">
            <table class="w-full text-sm">
                <thead><tr style="background:var(--c-surface2);border-bottom:1px solid var(--c-border)">
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Statut</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Nom</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Image</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Ports</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Actions</th>
                </tr></thead>
                <tbody>
                    <template x-for="c in filtered" :key="c.id">
                        <tr style="border-bottom:1px solid var(--c-border);background:var(--c-surface)">
                            <td class="px-3 py-2.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold"
                                    :style="c.state==='running'?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-danger-dim);color:var(--c-danger)'">
                                    <span class="w-1.5 h-1.5 rounded-full" :style="'background:'+(c.state==='running'?'var(--c-accent)':'var(--c-danger)')"></span>
                                    <span x-text="c.state"></span>
                                </span>
                            </td>
                            <td class="px-3 py-2.5 font-mono text-xs"><div x-text="c.name" style="color:var(--c-text);font-weight:600"></div><div x-text="c.id" style="color:var(--c-text3)"></div></td>
                            <td class="px-3 py-2.5 font-mono text-xs" style="color:var(--c-text2)" x-text="c.image"></td>
                            <td class="px-3 py-2.5 text-xs" style="color:var(--c-text3)" x-text="c.ports||'—'"></td>
                            <td class="px-3 py-2.5">
                                <div class="flex gap-1">
                                    <template x-if="c.state!=='running'"><button @click="action(c.id,'start')" class="px-2 py-1 text-xs rounded" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)">▶</button></template>
                                    <template x-if="c.state==='running'"><button @click="action(c.id,'stop')" class="px-2 py-1 text-xs rounded" style="background:var(--c-surface2);color:var(--c-text2);border:1px solid var(--c-border)">■</button></template>
                                    <button @click="action(c.id,'restart')" class="px-2 py-1 text-xs rounded" style="background:var(--c-surface2);color:var(--c-text2);border:1px solid var(--c-border)">↺</button>
                                    <button @click="logs(c.id,c.name)" class="px-2 py-1 text-xs rounded" style="background:var(--c-blue-dim);color:var(--c-blue)">Logs</button>
                                    <button @click="remove(c.id)" class="px-2 py-1 text-xs rounded" style="background:var(--c-danger-dim);color:var(--c-danger)">✕</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filtered.length===0"><td colspan="5" class="text-center py-8 text-sm" style="color:var(--c-text3)">Aucun container</td></tr>
                </tbody>
            </table>
        </div>
    </template>

    <template x-if="!loading && tab==='images'">
        <div class="rounded-lg overflow-hidden" style="border:1px solid var(--c-border)">
            <table class="w-full text-sm">
                <thead><tr style="background:var(--c-surface2);border-bottom:1px solid var(--c-border)">
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">ID</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Repository:Tag</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Taille</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Créé</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Actions</th>
                </tr></thead>
                <tbody>
                    <template x-for="img in filtered" :key="img.id">
                        <tr style="border-bottom:1px solid var(--c-border);background:var(--c-surface)">
                            <td class="px-3 py-2.5 font-mono text-xs" style="color:var(--c-text3)" x-text="img.id"></td>
                            <td class="px-3 py-2.5 font-mono text-xs"><span x-text="img.repository" style="color:var(--c-text)"></span>:<span x-text="img.tag" style="color:var(--c-accent)"></span></td>
                            <td class="px-3 py-2.5 text-xs" style="color:var(--c-text2)" x-text="img.size"></td>
                            <td class="px-3 py-2.5 text-xs" style="color:var(--c-text3)" x-text="img.created"></td>
                            <td class="px-3 py-2.5"><button @click="removeImg(img.id)" class="px-2 py-1 text-xs rounded" style="background:var(--c-danger-dim);color:var(--c-danger)">✕ Supprimer</button></td>
                        </tr>
                    </template>
                    <tr x-show="filtered.length===0"><td colspan="5" class="text-center py-8 text-sm" style="color:var(--c-text3)">Aucune image</td></tr>
                </tbody>
            </table>
        </div>
    </template>

    <template x-if="!loading && (tab==='volumes'||tab==='networks')">
        <div class="rounded-lg overflow-hidden" style="border:1px solid var(--c-border)">
            <table class="w-full text-sm">
                <tbody>
                    <template x-for="item in filtered" :key="item.Name||item.ID||Math.random()">
                        <tr style="border-bottom:1px solid var(--c-border);background:var(--c-surface)">
                            <template x-for="(val, idx) in Object.values(item)" :key="idx">
                                <td class="px-3 py-2.5 font-mono text-xs" style="color:var(--c-text2)" x-text="val||'—'"></td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="filtered.length===0"><td class="text-center py-8 text-sm" style="color:var(--c-text3)">Aucun élément</td></tr>
                </tbody>
            </table>
        </div>
    </template>

    <div x-show="logsModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.75)">
        <div class="w-full max-w-4xl rounded-xl flex flex-col" style="background:var(--c-surface);border:1px solid var(--c-border2);max-height:80vh">
            <div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid var(--c-border)">
                <span class="font-mono text-sm font-semibold" style="color:var(--c-accent)" x-text="'Logs — '+logsModal.name"></span>
                <button @click="logsModal.open=false" style="color:var(--c-text3)">✕</button>
            </div>
            <pre class="flex-1 overflow-auto p-4 text-xs font-mono whitespace-pre-wrap" style="color:#ccc;background:var(--c-bg)" x-text="logsModal.content"></pre>
        </div>
    </div>

    <div x-show="toast.show" x-transition class="fixed bottom-16 right-6 z-50 px-4 py-2 rounded-lg text-sm shadow-lg"
         :style="toast.ok?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-danger-dim);color:var(--c-danger)'"
         x-text="toast.msg"></div>
</div>
</x-filament-panels::page>
