const slugify = (value) => {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
};

const initializeArchiveAdmin = () => {
    const form = document.querySelector(
        '[data-archive-admin-form]'
    );

    if (!form) {
        return;
    }

    const sourceLocale =
        form.querySelector(
            '[data-archive-source-locale]'
        );

    const updateSourceBadges = () => {
        const current =
            sourceLocale?.value || 'pl';

        ['pl', 'en'].forEach((locale) => {
            const badge = form.querySelector(
                `[data-archive-source-badge="${locale}"]`
            );

            const status = form.querySelector(
                `[data-archive-status="${locale}"]`
            );

            const isSource =
                locale === current;

            badge?.classList.toggle(
                'is-visible',
                isSource
            );

            if (status) {
                status.disabled =
                    isSource;

                if (isSource) {
                    status.value =
                        'source';
                }
            }
        });
    };

    sourceLocale?.addEventListener(
        'change',
        updateSourceBadges
    );

    ['pl', 'en'].forEach((locale) => {
        const title = form.querySelector(
            `[data-archive-title="${locale}"]`
        );

        const slug = form.querySelector(
            `[data-archive-slug="${locale}"]`
        );

        if (!title || !slug) {
            return;
        }

        let slugTouched =
            slug.value.trim() !== '';

        slug.addEventListener('input', () => {
            slugTouched =
                slug.value.trim() !== '';
        });

        title.addEventListener('input', () => {
            if (!slugTouched) {
                slug.value =
                    slugify(title.value);
            }
        });
    });

    updateSourceBadges();
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeArchiveAdmin
    );
} else {
    initializeArchiveAdmin();
}
