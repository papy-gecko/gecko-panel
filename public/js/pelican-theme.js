(function() {
    const saved = localStorage.getItem('pelican-theme') || 'dark';
    applyTheme(saved);
    document.addEventListener('DOMContentLoaded', injectToggle);
    document.addEventListener('livewire:navigated', injectToggle);

    function applyTheme(theme) {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        localStorage.setItem('pelican-theme', theme);
        const btn = document.getElementById('theme-toggle');
        if (btn) btn.innerHTML = theme === 'dark' ? '☀' : '🌙';
    }

    function injectToggle() {
        if (document.getElementById('theme-toggle')) return;
        const btn = document.createElement('button');
        btn.id = 'theme-toggle';
        btn.innerHTML = localStorage.getItem('pelican-theme') === 'dark' ? '☀' : '🌙';
        btn.title = 'Changer le thème';
        btn.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;width:40px;height:40px;border-radius:50%;background:#111;border:1px solid #333;color:#888;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;transition:all .2s;';
        btn.onclick = () => applyTheme(localStorage.getItem('pelican-theme') === 'dark' ? 'light' : 'dark');
        document.body.appendChild(btn);
    }
})();
