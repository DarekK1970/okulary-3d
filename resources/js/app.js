const initNavigation = () => {
    const toggle = document.querySelector('[data-menu-toggle]');
    const navigation = document.querySelector('[data-primary-navigation]');
    const closeButton = document.querySelector('[data-menu-close]');
    const backdrop = document.querySelector('[data-menu-backdrop]');

    if (!toggle || !navigation) {
        return;
    }

    const closeMenu = () => {
        navigation.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('menu-open');
    };

    const openMenu = () => {
        navigation.classList.add('is-open');
        backdrop?.classList.add('is-visible');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('menu-open');
    };

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        isOpen ? closeMenu() : openMenu();
    });

    closeButton?.addEventListener('click', closeMenu);
    backdrop?.addEventListener('click', closeMenu);

    navigation.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 1220) {
            closeMenu();
        }
    });
};

const initGalleryTabs = () => {
    const tabs = document.querySelectorAll('.gallery-tab');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((item) => item.classList.remove('is-active'));
            tab.classList.add('is-active');
        });
    });
};

const initApp = () => {
    initNavigation();
    initGalleryTabs();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}
