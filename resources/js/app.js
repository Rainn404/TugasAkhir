import './bootstrap';

const sidebar = document.getElementById('adminSidebar');
const sidebarOverlay = document.getElementById('adminSidebarOverlay');
const sidebarToggle = document.querySelector('[data-admin-sidebar-toggle]');
const sidebarCloseButtons = document.querySelectorAll('[data-admin-sidebar-close]');
const publicNavMenu = document.getElementById('navbarFloatingMenu') || document.getElementById('navbarMain');
const publicNavToggle = document.querySelector('[data-bs-target="#navbarFloatingMenu"], [data-bs-target="#navbarMain"]');

publicNavMenu?.addEventListener('shown.bs.collapse', () => {
    publicNavToggle?.setAttribute('aria-label', 'Tutup menu navigasi');
});

publicNavMenu?.addEventListener('hidden.bs.collapse', () => {
    publicNavToggle?.setAttribute('aria-label', 'Buka menu navigasi');
});

publicNavMenu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        if (window.innerWidth >= 992 || !window.bootstrap?.Collapse) return;

        window.bootstrap.Collapse.getOrCreateInstance(publicNavMenu).hide();
    });
});


function setSidebarOpen(open) {
    if (!sidebar || !sidebarOverlay) return;

    sidebar.classList.toggle('show', open);
    sidebarOverlay.classList.toggle('show', open);
    if (sidebarToggle) {
        sidebarToggle.setAttribute('aria-expanded', String(open));
        sidebarToggle.setAttribute('aria-label', open ? 'Tutup menu operasional' : 'Buka menu operasional');
    }

    if (window.innerWidth < 992) {
        document.body.style.overflow = open ? 'hidden' : '';
    }
}

sidebarToggle?.addEventListener('click', (e) => {
    e.stopPropagation();
    setSidebarOpen(!sidebar.classList.contains('show'));
});

sidebarOverlay?.addEventListener('click', () => setSidebarOpen(false));

sidebarCloseButtons.forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        setSidebarOpen(false);
    });
});

sidebar?.querySelectorAll('.sidebar-link').forEach((link) => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            setSidebarOpen(false);
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setSidebarOpen(false);
});

window.matchMedia('(min-width: 992px)').addEventListener('change', (event) => {
    if (event.matches) {
        setSidebarOpen(false);
        document.body.style.overflow = '';
    }
});

const revealElements = document.querySelectorAll('[data-reveal]');

if (revealElements.length && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('reveal-enhanced');

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12 });

    revealElements.forEach((element) => revealObserver.observe(element));
}
