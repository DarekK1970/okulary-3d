const slugifyCatalog = (value) => value
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/ł/g, 'l')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-+/g, '-');

const initCatalogSlugs = () => {
    document.querySelectorAll('[data-catalog-slug-source]').forEach((source) => {
        const locale = source.dataset.catalogSlugSource;
        const target = document.querySelector(
            `[data-catalog-slug-target="${locale}"]`
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
                target.value = slugifyCatalog(source.value);
            }
        });
    });
};

const reindexVariants = () => {
    document.querySelectorAll('[data-variant-row]').forEach((row, index) => {
        row.querySelectorAll('[data-variant-field]').forEach((field) => {
            const key = field.dataset.variantField;
            field.name = `variants[${index}][${key}]`;

            if (key === 'sort_order') {
                field.value = index;
            }
        });
    });
};

const initVariants = () => {
    const container = document.querySelector('[data-variants-container]');
    const template = document.querySelector('[data-variant-template]');
    const addButton = document.querySelector('[data-add-variant]');

    if (!container || !template || !addButton) {
        return;
    }

    const bindRemove = (button) => {
        button.addEventListener('click', () => {
            if (container.querySelectorAll('[data-variant-row]').length <= 1) {
                window.alert('Produkt musi posiadać co najmniej jeden wariant.');
                return;
            }

            button.closest('[data-variant-row]')?.remove();
            reindexVariants();
        });
    };

    container.querySelectorAll('[data-remove-variant]').forEach(bindRemove);

    addButton.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('[data-variant-row]');

        container.appendChild(fragment);

        if (row) {
            bindRemove(row.querySelector('[data-remove-variant]'));
        }

        reindexVariants();
    });

    reindexVariants();
};

const initCatalog = () => {
    initCatalogSlugs();
    initVariants();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCatalog);
} else {
    initCatalog();
}
