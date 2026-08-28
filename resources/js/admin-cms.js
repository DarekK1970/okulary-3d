const slugify = (value) => value
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/ł/g, 'l')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-+/g, '-');

const initSlugGenerator = () => {
    const source = document.querySelector('[data-slug-source]');
    const target = document.querySelector('[data-slug-target]');

    if (!source || !target) {
        return;
    }

    let manuallyEdited = target.value.trim() !== '';

    target.addEventListener('input', () => {
        manuallyEdited = target.value.trim() !== '';
    });

    source.addEventListener('input', () => {
        if (!manuallyEdited) {
            target.value = slugify(source.value);
        }
    });
};

const initEditor = () => {
    document.querySelectorAll('[data-wysiwyg]').forEach((wrapper) => {
        const editor = wrapper.querySelector('[data-editor]');
        const output = wrapper.querySelector('[data-editor-output]');

        if (!editor || !output) {
            return;
        }

        const sync = () => {
            output.value = editor.innerHTML.trim();
        };

        wrapper.querySelectorAll('[data-command]').forEach((button) => {
            button.addEventListener('click', () => {
                editor.focus();
                const command = button.dataset.command;
                const value = button.dataset.value || null;

                document.execCommand(command, false, value);
                sync();
            });
        });

        wrapper.querySelector('[data-link]')?.addEventListener('click', () => {
            const url = window.prompt('Adres URL:');

            if (!url) {
                return;
            }

            editor.focus();
            document.execCommand('createLink', false, url);
            sync();
        });

        editor.addEventListener('input', sync);
        editor.closest('form')?.addEventListener('submit', sync);

        sync();
    });
};

const initStatus = () => {
    const select = document.querySelector('[data-status-select]');
    const dateField = document.querySelector('[data-publication-date]');
    const dateInput = dateField?.querySelector('input');

    if (!select || !dateField || !dateInput) {
        return;
    }

    const update = () => {
        const scheduled = select.value === 'scheduled';
        const published = select.value === 'published';

        dateField.hidden = !(scheduled || published);
        dateInput.required = scheduled;
    };

    select.addEventListener('change', update);
    update();
};

const initImagePreview = () => {
    const input = document.querySelector('[data-image-input]');
    const preview = document.querySelector('[data-image-preview]');
    const image = preview?.querySelector('img');

    if (!input || !preview || !image) {
        return;
    }

    input.addEventListener('change', () => {
        const [file] = input.files;

        if (!file) {
            preview.hidden = true;
            image.removeAttribute('src');
            return;
        }

        image.src = URL.createObjectURL(file);
        preview.hidden = false;
    });
};

const init = () => {
    initSlugGenerator();
    initEditor();
    initStatus();
    initImagePreview();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
