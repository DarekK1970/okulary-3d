document.querySelectorAll('[data-studio-image-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const image = input.parentElement.querySelector('[data-studio-image-preview]');
        if (!image || !input.files?.[0]) return;
        if (image.dataset.objectUrl) URL.revokeObjectURL(image.dataset.objectUrl);
        image.dataset.objectUrl = URL.createObjectURL(input.files[0]);
        image.src = image.dataset.objectUrl;
        image.hidden = false;
    });
});

const refresh = document.querySelector('[data-studio-job-refresh]');
if (refresh) setTimeout(() => window.location.reload(), Number(refresh.dataset.refreshAfter || 10000));

const sequenceInput = document.querySelector('[data-sequence-input]');
if (sequenceInput) sequenceInput.addEventListener('change', () => {
    const preview = document.querySelector('[data-sequence-preview]');
    const count = document.querySelector('[data-sequence-count]');
    preview.querySelectorAll('img').forEach((image) => URL.revokeObjectURL(image.src));
    preview.replaceChildren();
    [...sequenceInput.files].forEach((file, index) => {
        const figure = document.createElement('figure');
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = '';
        const caption = document.createElement('figcaption');
        caption.textContent = `${index + 1}. ${file.name}`;
        figure.append(image, caption);
        preview.append(figure);
    });
    count.textContent = sequenceInput.files.length ? count.dataset.countTemplate.replace(':count', sequenceInput.files.length) : '';
});
