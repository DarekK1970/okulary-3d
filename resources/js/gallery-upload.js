const initializeGalleryUpload = () => {
    document.querySelectorAll(
        '[data-gallery-upload]'
    ).forEach((input) => {
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
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeGalleryUpload
    );
} else {
    initializeGalleryUpload();
}
