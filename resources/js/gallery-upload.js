const initializeGalleryUpload = () => {
    const form = document.querySelector(
        '[data-gallery-upload-form]'
    );

    if (!form) {
        return;
    }

    const isSupportedUploadFile = (file) => {
        if (!file) {
            return false;
        }

        const name = file.name.toLowerCase();
        const type = file.type || '';

        return (
            type.startsWith('image/')
            || name.endsWith('.mpo')
        );
    };

    const applyDroppedFiles = (input, fileList) => {
        if (!fileList || fileList.length === 0) {
            return;
        }

        const file = Array.from(fileList)
            .find(isSupportedUploadFile);

        if (!file) {
            return;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
        input.dispatchEvent(
            new Event('change', {
                bubbles: true,
            })
        );
    };

    const bindDropzone = (input) => {
        const bucket = input.closest(
            '.gallery-upload-box'
        );

        if (!bucket) {
            return;
        }

        ['dragenter', 'dragover'].forEach((eventName) => {
            bucket.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                bucket.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
            bucket.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                bucket.classList.remove('is-dragover');
            });
        });

        bucket.addEventListener('drop', (event) => {
            applyDroppedFiles(
                input,
                event.dataTransfer?.files
            );
        });
    };

    const modeSelect = form.querySelector(
        '[data-gallery-upload-type]'
    );
    const singleUpload = form.querySelector(
        '[data-gallery-single-upload]'
    );
    const splitUpload = form.querySelector(
        '[data-gallery-split-upload]'
    );
    const sourceInput = form.querySelector(
        '[name="source_image"]'
    );
    const leftInput = form.querySelector(
        '[name="left_image"]'
    );
    const rightInput = form.querySelector(
        '[name="right_image"]'
    );

    const updateMode = () => {
        const mode = modeSelect?.value || 'stereo_pair';
        const isSplit = mode === 'left_right';

        if (singleUpload) {
            singleUpload.hidden = isSplit;
        }

        if (splitUpload) {
            splitUpload.hidden = !isSplit;
        }

        if (sourceInput) {
            sourceInput.required = !isSplit;
            sourceInput.toggleAttribute(
                'required',
                !isSplit
            );
        }

        if (leftInput) {
            leftInput.required = isSplit;
            leftInput.toggleAttribute(
                'required',
                isSplit
            );
        }

        if (rightInput) {
            rightInput.required = isSplit;
            rightInput.toggleAttribute(
                'required',
                isSplit
            );
        }
    };

    modeSelect?.addEventListener(
        'change',
        updateMode
    );
    updateMode();

    document.querySelectorAll(
        '[data-gallery-upload]'
    ).forEach((input) => {
        bindDropzone(input);

        const side =
            input.dataset.galleryUpload;

        const preview =
            document.querySelector(
                `[data-gallery-preview="${side}"]`
            );

        const filename =
            document.querySelector(
                `[data-gallery-filename="${side}"]`
            );

        let currentUrl = null;

        input.addEventListener('change', () => {
            const file =
                input.files?.[0];

            if (currentUrl) {
                URL.revokeObjectURL(
                    currentUrl
                );

                currentUrl = null;
            }

            if (!file) {
                if (preview) {
                    preview.removeAttribute('src');
                }

                if (filename) {
                    filename.textContent =
                        file ? file.name : '';
                }

                return;
            }

            currentUrl =
                URL.createObjectURL(file);

            if (preview) {
                preview.src = currentUrl;
                preview.classList.add(
                    'has-image'
                );
            }

            if (filename) {
                filename.textContent =
                    file.name;
            }
        });
    });

    const sourceUpload = form.querySelector(
        '[data-gallery-upload-source]'
    );

    if (sourceUpload) {
        bindDropzone(sourceUpload);

        const preview = form.querySelector(
            '[data-gallery-preview="source"]'
        );
        const filename = form.querySelector(
            '[data-gallery-filename="source"]'
        );

        let currentUrl = null;

        sourceUpload.addEventListener('change', () => {
            const file = sourceUpload.files?.[0];

            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                currentUrl = null;
            }

            if (!file) {
                if (preview) {
                    preview.removeAttribute('src');
                }

                if (filename) {
                    filename.textContent = '';
                }

                return;
            }

            currentUrl = URL.createObjectURL(file);

            if (preview) {
                preview.src = currentUrl;
                preview.classList.add('has-image');
            }

            if (filename) {
                filename.textContent = file.name;
            }
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeGalleryUpload
    );
} else {
    initializeGalleryUpload();
}
