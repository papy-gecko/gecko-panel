<x-filament-panels::page>
<div x-data="{
    cmd:'', cwd:'/var/www/pelican', history:[], running:false,
    cmdHistory:[], histIdx:-1,
    showPty:false, ptyCmd:'',
    ptyApps:['nano','vim','vi','htop','top','less','more','man','mc','ncdu','watch'],
    isPtyCmd(c){
        const first=c.trim().split(/\s+/)[0];
        return this.ptyApps.includes(first);
    },
    init(){this.$nextTick(()=>this.$refs.input?.focus());},
    async runCmd(c){
        if(!c.trim())return;
        this.cmdHistory.push(c.trim());
        if(c.trim()==='clear'){this.history=[];return;}
        if(this.isPtyCmd(c.trim())){
            this.ptyCmd=c.trim();
            this.showPty=true;
            this.$nextTick(()=>{
                const iframe=this.$refs.ptyFrame;
                if(iframe) iframe.src='/gotty/';
            });
            return;
        }
        this.running=true;
        try{
            const r=await fetch('/cockpit/terminal/execute',{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({command:c,cwd:this.cwd})});
            const d=await r.json();
            if(d.cwd)this.cwd=d.cwd;
            this.history.push({cmd:c.split('\n')[0].trim()+(c.includes('\n')?'...':''),cwd:this.cwd,output:d.output||'',error:d.error||false});
        }catch(e){this.history.push({cmd:c.trim(),cwd:this.cwd,output:'Erreur réseau',error:true});}
        this.running=false;
        this.$nextTick(()=>{this.$refs.output.scrollTop=this.$refs.output.scrollHeight;this.$refs.input?.focus();});
    },
    async run(){
        const text=this.cmd;
        this.histIdx=-1; this.cmd='';
        if(!text.trim())return;
        if(text.includes('\n')){
            await this.runCmd(text);
        } else {
            await this.runCmd(text);
        }
    },
    key(e){
        if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();this.run();}
        else if(e.key==='ArrowUp'&&!this.cmd){e.preventDefault();if(this.histIdx<this.cmdHistory.length-1){this.histIdx++;this.cmd=this.cmdHistory[this.cmdHistory.length-1-this.histIdx];}}
        else if(e.key==='ArrowDown'){e.preventDefault();if(this.histIdx>0){this.histIdx--;this.cmd=this.cmdHistory[this.cmdHistory.length-1-this.histIdx];}else{this.histIdx=-1;this.cmd='';}}
        else if(e.ctrlKey&&e.key==='l'){e.preventDefault();this.history=[];}
    }
}" x-init="init()" class="relative flex flex-col gap-4" style="height:calc(100vh - 180px)">

    <div x-show="showPty" x-cloak class="absolute inset-0 z-50 flex flex-col rounded-lg overflow-hidden" style="background:#050505;border:1px solid var(--c-border2)">
        <div class="flex items-center justify-between px-4 py-2 shrink-0" style="background:#080808;border-bottom:1px solid var(--c-border2)">
            <span class="font-mono text-xs" style="color:var(--c-accent)" x-text="'PTY — ' + ptyCmd"></span>
            <button @click="showPty=false;$refs.ptyFrame.src=''" class="px-3 py-1 text-xs rounded" style="background:#cc3333;color:#fff">✕ Fermer</button>
        </div>
        <iframe x-ref="ptyFrame" src="" style="flex:1;border:none;width:100%;height:100%"></iframe>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex gap-1.5"><span class="w-3 h-3 rounded-full" style="background:#cc3333"></span><span class="w-3 h-3 rounded-full" style="background:#cc7700"></span><span class="w-3 h-3 rounded-full" style="background:#7fdb4f"></span></div>
            <span class="text-xs font-mono" style="color:var(--c-text3)">root@gecko:<span x-text="cwd" style="color:var(--c-accent)"></span></span>
        </div>
        <button @click="history=[]" class="px-3 py-1 text-xs rounded" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text3)">Effacer</button>
    </div>

    <div class="flex-1 rounded-lg overflow-hidden flex flex-col" style="background:#050505;border:1px solid var(--c-border2);min-height:400px">
        <div x-ref="output" class="flex-1 overflow-y-auto p-4 font-mono text-xs leading-relaxed" style="color:#d4d4d4">
            <div style="color:var(--c-accent)" class="mb-3">Gecko Web Terminal — bash<br><span style="color:var(--c-text3)">nano/vim/htop → PTY automatique · ↑↓ historique · Ctrl+L effacer · Shift+Enter nouvelle ligne</span></div>
            <template x-for="(entry,i) in history" :key="i">
                <div class="mb-1">
                    <div class="flex gap-2"><span style="color:var(--c-accent)">root@gecko:<span x-text="entry.cwd" style="color:var(--c-blue)"></span>$</span><span style="color:#fff" x-text="entry.cmd"></span></div>
                    <pre x-show="entry.output" class="whitespace-pre-wrap mt-0.5" :style="entry.error?'color:var(--c-danger)':'color:#aaa'" x-text="entry.output"></pre>
                </div>
            </template>
            <div x-show="running" class="flex items-center gap-2"><span style="color:var(--c-accent)">$</span><span class="animate-pulse" style="color:var(--c-accent)">▋</span></div>
        </div>
        <div class="flex items-center gap-2 px-4 py-3" style="border-top:1px solid var(--c-border2);background:#080808">
            <span class="font-mono text-xs shrink-0" style="color:var(--c-accent)">root@gecko:<span x-text="cwd" style="color:var(--c-blue)"></span>$</span>
            <textarea x-ref="input" x-model="cmd" @keydown="key($event)" :disabled="running" placeholder="Entrez une commande… (Shift+Enter nouvelle ligne, Enter exécute)" autocomplete="off" spellcheck="false" rows="1" @input="$el.style.height='auto';$el.style.height=$el.scrollHeight+'px'" class="flex-1 bg-transparent font-mono text-xs outline-none resize-none" style="color:#fff;caret-color:var(--c-accent);line-height:1.5"></textarea>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <template x-for="s in ['ls -la','df -h','free -h','docker ps','nano /etc/pelican/config.yml','htop','systemctl list-units --state=running']" :key="s">
            <button @click="cmd=s; run()" class="px-2.5 py-1 text-xs rounded font-mono" style="background:var(--c-surface2);border:1px solid var(--c-border);color:var(--c-text3)" x-text="s"></button>
        </template>
    </div>
</div>
</x-filament-panels::page>
