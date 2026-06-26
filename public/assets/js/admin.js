(function(){
  // MOBILE slide-in
  const mobBtn   = document.getElementById('btnSidebarMobile') || document.getElementById('btnSidebar');
  const sidebar  = document.getElementById('sidebar');
  const backdrop = document.getElementById('backdrop');

  function setMobileOpen(on){
    if (!sidebar) return;
    sidebar.classList.toggle('open', !!on);
    if (backdrop) backdrop.classList.toggle('show', !!on);
    document.body.classList.toggle('sidebar-open', !!on);
  }

  if (mobBtn && sidebar){
    mobBtn.addEventListener('click', (e)=>{ e.stopPropagation(); setMobileOpen(!sidebar.classList.contains('open')); });
    document.addEventListener('click', (e)=>{
      if (!sidebar.classList.contains('open')) return;
      const inside = sidebar.contains(e.target) || (mobBtn && mobBtn.contains(e.target));
      if (!inside) setMobileOpen(false);
    });
    if (backdrop) backdrop.addEventListener('click', ()=> setMobileOpen(false));
    window.addEventListener('resize', ()=> { if (window.innerWidth >= 992) setMobileOpen(false); });
  }

  // DESKTOP mini (ikon)
  const miniBtn = document.getElementById('btnSidebarMini');
  
  const applyMini = on => { 
    document.body.classList.toggle('app-mini', !!on); 
    localStorage.setItem('app-mini', on ? '1' : '0');
  };

  if (miniBtn) {
    miniBtn.addEventListener('click', ()=> applyMini(!document.body.classList.contains('app-mini')));
  }

  // Auto expand when clicking links in mini mode
  if (sidebar) {
    sidebar.addEventListener('click', (e) => {
      const isMini = document.body.classList.contains('app-mini');
      const isDesktop = window.innerWidth >= 992;
      
      if (isMini && isDesktop) {
        const menuItem = e.target.closest('.menu-item');
        if (menuItem) {
          // If it's a simple link (not a parent toggle), navigation will happen naturally.
          // We just expand the sidebar for the next view.
          applyMini(false);
          
          if (menuItem.classList.contains('menu-parent')) {
            // For parents, we might need to expand the specific collapse
            const targetId = menuItem.getAttribute('href');
            if (targetId && targetId.startsWith('#')) {
              const targetEl = document.querySelector(targetId);
              if (targetEl && !targetEl.classList.contains('show')) {
                 const bsCollapse = bootstrap.Collapse.getInstance(targetEl) || new bootstrap.Collapse(targetEl);
                 bsCollapse.show();
              }
            }
          }
        }
      }
    });
  }

  // Initialize from localStorage
  if (localStorage.getItem('app-mini') === '1') {
    applyMini(true);
  } else {
    applyMini(false);
  }
})();
