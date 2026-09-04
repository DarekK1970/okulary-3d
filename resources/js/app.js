import '../css/partners.css';

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
        link.addEventListener('click', () => {
            navigation.querySelectorAll('details[open]').forEach((dropdown) => {
                dropdown.removeAttribute('open');
            });
            closeMenu();
        });
    });

    document.addEventListener('click', (event) => {
        if (!navigation.contains(event.target)) {
            navigation.querySelectorAll('details[open]').forEach((dropdown) => {
                dropdown.removeAttribute('open');
            });
        }
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

const initPartnerCarousels = () => {
    document.querySelectorAll('[data-partner-carousel]').forEach((carousel) => {
        const sequence = carousel.querySelector('[data-partner-sequence]');

        if (!sequence) {
            return;
        }

        const update = () => {
            const overflowing = sequence.scrollWidth > carousel.clientWidth + 2;
            carousel.classList.toggle('is-overflowing', overflowing);

            if (overflowing) {
                const duration = Math.max(20, Math.round(sequence.scrollWidth / 55));
                carousel.style.setProperty('--partner-carousel-duration', `${duration}s`);
            } else {
                carousel.style.removeProperty('--partner-carousel-duration');
            }
        };

        update();

        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(update);
            observer.observe(carousel);
            observer.observe(sequence);
        } else {
            window.addEventListener('resize', update);
        }
    });
};

const initApp = () => {
    initNavigation();
    initGalleryTabs();
    initPartnerCarousels();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}
