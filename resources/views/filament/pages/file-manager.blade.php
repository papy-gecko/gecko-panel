<x-filament-panels::page>
<div x-data="{
    path:'/', items:[], loading:false,
    editor:{open:false,path:'',content:'',saving:false},
    rename:{open:false,path:'',dir:'',oldName:'',newName:''},
    newFolder:{open:false,name:''},
    toast:{show:false,msg:'',ok:true},
    dragging:false,

    init(){this.browse(this.path);},

    async get(url){
        const r=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        return r.json();
    },
    async post(url,body){
        const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(body)});
        return r.json();
    },

    async browse(p){
        this.loading=true;
        const d=await this.get('/cockpit/files/browse?path='+encodeURIComponent(p));
        if(d.error){this.notify(d.error,false);}
        else{this.path=d.path;this.items=d.items;}
        this.loading=false;
    },

    async openFile(p){
        const d=await this.get('/cockpit/files/read?path='+encodeURIComponent(p));
        if(d.error){this.notify(d.error,false);return;}
        this.editor={open:true,path:p,content:d.content,saving:false};
    },

    async saveFile(){
        this.editor.saving=true;
        const d=await this.post('/cockpit/files/write',{path:this.editor.path,content:this.editor.content});
        this.editor.saving=false;
        this.notify(d.ok?'Fichier sauvegarde':d.error,d.ok);
    },

    downloadItem(p, isDir){
        if(isDir) this.notify('Creation du zip en cours...', true);
        const a = document.createElement('a');
        a.href = '/cockpit/files/download?path=' + encodeURIComponent(p);
        a.target = '_blank';
        a.rel = 'noopener';
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    },

    openRename(item){
        this.rename={open:true,path:item.path,dir:this.path,oldName:item.name,newName:item.name};
        this.$nextTick(()=>this.$refs.renameInput&&this.$refs.renameInput.select());
    },

    async doRename(){
        if(!this.rename.newName||this.rename.newName===this.rename.oldName){this.rename.open=false;return;}
        const to=this.rename.dir.replace(/\/+$/,'')+'/'+this.rename.newName;
        const d=await this.post('/cockpit/files/rename',{from:this.rename.path,to:to});
        this.rename.open=false;
        this.notify(d.ok?'Renomme':d.error,d.ok);
        if(d.ok) this.browse(this.path);
    },

    async doMkdir(){
        if(!this.newFolder.name){this.newFolder.open=false;return;}
        const p=this.path.replace(/\/+$/,'')+'/'+this.newFolder.name;
        const d=await this.post('/cockpit/files/mkdir',{path:p});
        this.newFolder={open:false,name:''};
        this.notify(d.ok?'Dossier cree':d.error,d.ok);
        if(d.ok) this.browse(this.path);
    },

    async deleteItem(p,name){
        if(!confirm('Supprimer : '+name+' ?'))return;
        const r=await fetch('/cockpit/files/delete',{method:'DELETE',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content},body:JSON.stringify({path:p})});
        const d=await r.json();
        this.notify(d.ok?'Supprime':d.error,d.ok);
        if(d.ok) this.browse(this.path);
    },

    async uploadFiles(files){
        if(!files||!files.length) return;
        const fd=new FormData();
        for(const f of files) fd.append('files[]',f);
        fd.append('dir',this.path);
        const r=await fetch('/cockpit/files/upload',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'X-Requested-With':'XMLHttpRequest'},body:fd});
        const d=await r.json();
        this.notify(d.ok?d.files.length+' fichier(s) uploade(s)':d.error,d.ok);
        if(d.ok) this.browse(this.path);
    },

    handleDrop(e){
        e.preventDefault();this.dragging=false;
        const files=Array.from(e.dataTransfer.files);
        if(files.length) this.uploadFiles(files);
    },

    notify(msg,ok=true){this.toast={show:true,msg,ok};setTimeout(()=>this.toast.show=false,3500);}
}"
    x-init="init()"
    @dragover.prevent="dragging=true"
    @dragleave.prevent="dragging=false"
    @drop.prevent="handleDrop($event)"
    class="space-y-3">

    {{-- Barre du haut --}}
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="browse('/')" class="px-2 py-1.5 text-xs rounded" style="background:var(--c-surface2);color:var(--c-text3);border:1px solid var(--c-border)">&#8962;</button>
        <div class="flex-1 font-mono text-sm px-3 py-1.5 rounded" style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-accent)" x-text="path"></div>
        <button @click="browse(path)" title="Actualiser" class="px-2 py-1.5 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text2)">&#8635;</button>
        <button @click="newFolder.open=true;$nextTick(()=>$refs.mkdirInput&&$refs.mkdirInput.focus())" title="Nouveau dossier" class="px-2 py-1.5 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text2)">&#128193;+</button>
        <label title="Uploader des fichiers" class="px-2 py-1.5 text-xs rounded cursor-pointer" style="background:var(--c-accent-dim);border:1px solid var(--c-accent-border);color:var(--c-accent)">
            &#8679; Upload
            <input type="file" multiple class="hidden" @change="uploadFiles(Array.from($event.target.files));$event.target.value=''">
        </label>
    </div>

    {{-- Breadcrumb --}}
    <div class="flex gap-1 flex-wrap text-xs font-mono items-center" style="color:var(--c-text3)">
        <template x-for="(part,i) in path.split('/').filter(p=>p)" :key="i">
            <span class="flex items-center gap-1">
                <button @click="browse('/'+path.split('/').filter(p=>p).slice(0,i+1).join('/'))" style="color:var(--c-accent)" x-text="part"></button>
                <span x-show="i < path.split('/').filter(p=>p).length-1" style="color:var(--c-text3)">/</span>
            </span>
        </template>
    </div>

    {{-- Zone drag & drop --}}
    <div x-show="dragging" class="border-2 border-dashed rounded-lg py-6 text-center text-sm" style="border-color:var(--c-accent);color:var(--c-accent);background:var(--c-accent-dim)">
        Deposez les fichiers ici pour les uploader
    </div>

    {{-- Nouveau dossier inline --}}
    <div x-show="newFolder.open" x-cloak class="flex items-center gap-2 p-2 rounded" style="background:var(--c-surface2);border:1px solid var(--c-border)">
        <span class="text-xs" style="color:var(--c-text3)">Nouveau dossier :</span>
        <input x-ref="mkdirInput" x-model="newFolder.name"
            @keydown.enter="doMkdir()"
            @keydown.escape="newFolder.open=false;newFolder.name=''"
            placeholder="nom-du-dossier"
            class="flex-1 px-2 py-1 text-xs font-mono rounded outline-none"
            style="background:var(--c-bg);border:1px solid var(--c-border2);color:var(--c-text)">
        <button @click="doMkdir()" class="px-3 py-1 text-xs rounded" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)">Creer</button>
        <button @click="newFolder.open=false;newFolder.name=''" class="px-2 py-1 text-xs rounded" style="color:var(--c-text3)">&#x2715;</button>
    </div>

    <div x-show="loading" class="text-center py-8 text-sm" style="color:var(--c-text3)">Chargement...</div>

    {{-- Table --}}
    <div x-show="!loading" class="rounded-lg overflow-hidden" style="border:1px solid var(--c-border)">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:var(--c-surface2);border-bottom:1px solid var(--c-border)">
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold" style="color:var(--c-text3)">Nom</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold hidden md:table-cell" style="color:var(--c-text3)" width="80">Taille</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold hidden md:table-cell" style="color:var(--c-text3)" width="130">Modifie</th>
                    <th class="text-left px-3 py-2 text-xs uppercase font-semibold hidden lg:table-cell" style="color:var(--c-text3)" width="55">Perms</th>
                    <th width="200"></th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom:1px solid var(--c-border);background:var(--c-surface)">
                    <td class="px-3 py-2 font-mono text-xs" colspan="5">
                        <button @click="browse(path.split('/').slice(0,-1).join('/') || '/')" style="color:var(--c-text3)">&#128193; ..</button>
                    </td>
                </tr>
                <template x-for="item in items" :key="item.path">
                    <tr style="border-bottom:1px solid var(--c-border);background:var(--c-surface)" class="hover:brightness-110">
                        <td class="px-3 py-2 font-mono text-xs">
                            <template x-if="rename.open && rename.path===item.path">
                                <div class="flex items-center gap-1">
                                    <input x-ref="renameInput" x-model="rename.newName"
                                        @keydown.enter="doRename()"
                                        @keydown.escape="rename.open=false"
                                        class="px-2 py-0.5 text-xs font-mono rounded outline-none"
                                        style="background:var(--c-bg);border:1px solid var(--c-accent);color:var(--c-text);min-width:160px">
                                    <button @click="doRename()" class="px-2 py-0.5 text-xs rounded" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)">&#10003;</button>
                                    <button @click="rename.open=false" class="px-1 py-0.5 text-xs" style="color:var(--c-text3)">&#x2715;</button>
                                </div>
                            </template>
                            <template x-if="!(rename.open && rename.path===item.path)">
                                <button @click="item.type==='dir'?browse(item.path):openFile(item.path)"
                                    :style="item.type==='dir'?'color:var(--c-blue)':'color:var(--c-text)'"
                                    x-text="(item.type==='dir'?'&#128193; ':'&#128196; ')+item.name"></button>
                            </template>
                        </td>
                        <td class="px-3 py-2 text-xs hidden md:table-cell" style="color:var(--c-text3)" x-text="item.size"></td>
                        <td class="px-3 py-2 text-xs font-mono hidden md:table-cell" style="color:var(--c-text3)" x-text="item.modified"></td>
                        <td class="px-3 py-2 text-xs font-mono hidden lg:table-cell" style="color:var(--c-text3)" x-text="item.perms"></td>
                        <td class="px-3 py-2">
                            <div class="flex gap-1 justify-end flex-wrap">
                                <template x-if="item.type==='file'">
                                    <button @click="openFile(item.path)" class="px-2 py-0.5 text-xs rounded" style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)">Editer</button>
                                </template>
                                <button @click="downloadItem(item.path, item.type==='dir')" class="px-2 py-0.5 text-xs rounded" style="background:#1a3a1a;color:#4ade80;border:1px solid #2d5a2d" title="Telecharger">&#8595;</button>
                                <button @click="openRename(item)" class="px-2 py-0.5 text-xs rounded" style="background:var(--c-surface2);color:var(--c-text2);border:1px solid var(--c-border)" title="Renommer">&#9998;</button>
                                <button @click="deleteItem(item.path,item.name)" class="px-2 py-0.5 text-xs rounded" style="background:var(--c-danger-dim);color:var(--c-danger)" title="Supprimer">&#x2715;</button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Editeur plein ecran --}}
    <div x-show="editor.open" x-transition class="fixed inset-0 z-50 flex flex-col" style="background:#050505">
        <div class="flex items-center justify-between px-4 py-2" style="background:var(--c-surface);border-bottom:1px solid var(--c-border2)">
            <span class="font-mono text-xs" style="color:var(--c-accent)" x-text="editor.path"></span>
            <div class="flex gap-2">
                <button @click="saveFile()" :disabled="editor.saving"
                    class="px-3 py-1.5 text-xs rounded font-medium"
                    style="background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)"
                    x-text="editor.saving?'Sauvegarde...':'&#128190; Sauvegarder'"></button>
                <button @click="editor.open=false" class="px-3 py-1.5 text-xs rounded" style="background:var(--c-danger-dim);color:var(--c-danger)">&#x2715; Fermer</button>
            </div>
        </div>
        <textarea x-model="editor.content"
            class="flex-1 p-4 font-mono text-xs resize-none outline-none"
            style="background:#080808;color:#d4d4d4;tab-size:4"
            spellcheck="false"
            @keydown.tab.prevent="const s=this.selectionStart;this.value=this.value.substring(0,s)+'    '+this.value.substring(this.selectionEnd);this.selectionStart=this.selectionEnd=s+4;editor.content=this.value"
            @keydown.ctrl.s.prevent="saveFile()"></textarea>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition
        class="fixed bottom-16 right-6 z-50 px-4 py-2 rounded-lg text-sm shadow-lg"
        :style="toast.ok?'background:var(--c-accent-dim);color:var(--c-accent);border:1px solid var(--c-accent-border)':'background:var(--c-danger-dim);color:var(--c-danger)'"
        x-text="toast.msg"></div>
</div>
</x-filament-panels::page>
