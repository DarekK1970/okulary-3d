const slugifyLocalized = (value) => value
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/ł/g, 'l')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-+/g, '-');

const initTranslationTabs = () => {
    const tabs = document.querySelectorAll('[data-translation-tab]');
    const panes = document.querySelectorAll('[data-translation-pane]');

    if (!tabs.length || !panes.length) {
        return;
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const locale = tab.dataset.translationTab;

            tabs.forEach((item) => item.classList.remove('is-active'));
            panes.forEach((pane) => pane.classList.remove('is-active'));

            tab.classList.add('is-active');

            document
                .querySelector(`[data-translation-pane="${locale}"]`)
                ?.classList.add('is-active');
        });
    });
};

const initLocalizedSlugs = () => {
    document.querySelectorAll('[data-slug-source]').forEach((source) => {
        const locale = source.dataset.slugSource;
        const target = document.querySelector(
            `[data-slug-target="${locale}"]`
        );

        if (!target) {
            return;
        }

        let manuallyEdited = target.value.trim() !== '';

        target.addEventListener('input', () => {
            manuallyEdited = target.value.trim() !== '';
        });

        source.addEventListener('input', () => {
            if (!manuallyEdited) {
                target.value = slugifyLocalized(source.value);
            }
        });
    });
};

const initSourceLanguage = () => {
    const select = document.querySelector('[data-source-locale]');

    if (!select) {
        return;
    }

    const update = () => {
        const sourceLocale = select.value;

        document.querySelectorAll('[data-source-note]').forEach((note) => {
            note.hidden = note.dataset.sourceNote !== sourceLocale;
        });

        document.querySelectorAll('[data-translation-workflow]').forEach((field) => {
            const isSource = field.dataset.translationWorkflow === sourceLocale;
            field.hidden = isSource;
        });
    };

    select.addEventListener('change', update);
    update();
};

const init = () => {
    initTranslationTabs();
    initLocalizedSlugs();
    initSourceLanguage();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
