const initMediaPicker = () => {
    const modal = document.querySelector('[data-media-picker-modal]');
    const openButton = document.querySelector('[data-media-picker-open]');

    if (!modal || !openButton) {
        return;
    }

    const hiddenId = document.querySelector('[data-hero-media-id]');
    const preview = document.querySelector('[data-hero-selected-preview]');
    const previewImage = document.querySelector('[data-hero-selected-image]');
    const previewName = document.querySelector('[data-hero-selected-name]');
    const upload = document.querySelector('[data-hero-upload]');
    const removeCheckbox = document.querySelector('[data-remove-hero]');
    const search = modal.querySelector('[data-media-picker-search]');
    const items = modal.querySelectorAll('[data-media-picker-item]');

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('media-picker-open');
    };

    const open = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('media-picker-open');
        setTimeout(() => search?.focus(), 20);
    };

    openButton.addEventListener('click', open);

    modal.querySelectorAll('[data-media-picker-close]').forEach((button) => {
        button.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            close();
        }
    });

    items.forEach((item) => {
        item.addEventListener('click', () => {
            if (hiddenId) {
                hiddenId.value = item.dataset.mediaId || '';
            }

            if (preview && previewImage) {
                previewImage.src = item.dataset.mediaUrl || '';
                preview.hidden = false;
            }

            if (previewName) {
                previewName.textContent = item.dataset.mediaName || '';
            }

            if (upload) {
                upload.value = '';
            }

            if (removeCheckbox) {
                removeCheckbox.checked = false;
            }

            close();
        });
    });

    search?.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();

        items.forEach((item) => {
            const haystack = item.dataset.mediaSearch || '';
            item.hidden = query !== '' && !haystack.includes(query);
        });
    });
};

const initHeroUploadPriority = () => {
    const upload = document.querySelector('[data-hero-upload]');
    const hiddenId = document.querySelector('[data-hero-media-id]');
    const removeCheckbox = document.querySelector('[data-remove-hero]');

    upload?.addEventListener('change', () => {
        if (upload.files?.length) {
            if (hiddenId) {
                hiddenId.value = '';
            }

            if (removeCheckbox) {
                removeCheckbox.checked = false;
            }
        }
    });

    removeCheckbox?.addEventListener('change', () => {
        if (!removeCheckbox.checked) {
            return;
        }

        if (hiddenId) {
            hiddenId.value = '';
        }

        if (upload) {
            upload.value = '';
        }
    });
};

const init = () => {
    initMediaPicker();
    initHeroUploadPriority();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
