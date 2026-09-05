import {
    drawCover,
} from './mpo-utils';

class CommunityStereoViewer {
    constructor(root) {
        this.root = root;
        this.canvas = root.querySelector(
            '[data-gallery-canvas]'
        );
        this.left = null;
        this.right = null;
        this.swapped = false;
        this.wiggleFrame = 0;
        this.wiggleTimer = null;

        this.bind();
        this.load();
        this.root.communityViewer = this;
    }

    bind() {
        this.root.querySelector(
            '[data-gallery-mode]'
        )?.addEventListener('change', () => {
            this.updateWiggle();
            this.render();
        });

        this.root.querySelector(
            '[data-gallery-action="swap"]'
        )?.addEventListener('click', () => {
            this.swapped = !this.swapped;
            this.render();
        });
    }

    async loadImage(url) {
        return await new Promise((resolve, reject) => {
            const image = new Image();

            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = url;
        });
    }

    async load() {
        this.setStatus(
            this.root.dataset.loading || 'Loading…'
        );

        try {
            [this.left, this.right] =
                await Promise.all([
                    this.loadImage(
                        this.root.dataset.leftUrl
                    ),
                    this.loadImage(
                        this.root.dataset.rightUrl
                    ),
                ]);

            this.root.querySelector(
                '[data-gallery-empty]'
            )?.classList.add('is-hidden');

            this.setStatus(
                this.root.dataset.ratingSummary || ''
            );

            this.updateWiggle();
            this.render();
        } catch (error) {
            console.error(error);

            this.setStatus(
                this.root.dataset.error
                || 'Image loading failed.'
            );
        }
    }

    async updateImages(leftUrl, rightUrl, ratingSummary) {
        this.root.classList.add('is-switching');
        this.setStatus(
            this.root.dataset.loading || 'Loading…'
        );

        this.root.dataset.leftUrl = leftUrl;
        this.root.dataset.rightUrl = rightUrl;
        this.root.dataset.ratingSummary = ratingSummary || '';

        try {
            [this.left, this.right] =
                await Promise.all([
                    this.loadImage(leftUrl),
                    this.loadImage(rightUrl),
                ]);

            this.setStatus(this.root.dataset.ratingSummary);
            this.updateWiggle();
            this.render();
        } catch (error) {
            console.error(error);

            this.setStatus(
                this.root.dataset.error
                || 'Image loading failed.'
            );
        } finally {
            window.setTimeout(
                () => this.root.classList.remove('is-switching'),
                180
            );
        }
    }

    images() {
        return this.swapped
            ? [this.right, this.left]
            : [this.left, this.right];
    }

    mode() {
        return (
            this.root.querySelector(
                '[data-gallery-mode]'
            )?.value
            || 'parallel'
        );
    }

    frameSize() {
        const [left, right] = this.images();

        const width = Math.min(
            left.width,
            right.width
        );

        const height = Math.min(
            left.height,
            right.height
        );

        const maxDimension = 1600;
        const pairMultiplier =
            ['parallel', 'cross']
                .includes(this.mode())
                ? 2
                : 1;

        const scale = Math.min(
            1,
            maxDimension
                / Math.max(
                    width * pairMultiplier,
                    height
                )
        );

        return {
            width: Math.max(
                1,
                Math.round(width * scale)
            ),
            height: Math.max(
                1,
                Math.round(height * scale)
            ),
        };
    }

    layers(width, height) {
        return this.images().map((image) => {
            const canvas =
                document.createElement('canvas');

            canvas.width = width;
            canvas.height = height;

            drawCover(
                canvas.getContext('2d'),
                image,
                width,
                height
            );

            return canvas;
        });
    }

    render() {
        if (!this.left || !this.right) {
            return;
        }

        const mode = this.mode();
        const size = this.frameSize();
        const layers =
            this.layers(
                size.width,
                size.height
            );

        const ctx = this.canvas.getContext(
            '2d',
            {
                willReadFrequently:
                    mode === 'anaglyph',
            }
        );

        if (
            mode === 'parallel'
            || mode === 'cross'
        ) {
            this.canvas.width =
                size.width * 2;

            this.canvas.height =
                size.height;

            const first =
                mode === 'cross'
                    ? layers[1]
                    : layers[0];

            const second =
                mode === 'cross'
                    ? layers[0]
                    : layers[1];

            ctx.drawImage(first, 0, 0);
            ctx.drawImage(
                second,
                size.width,
                0
            );
        } else if (mode === 'wiggle') {
            this.canvas.width =
                size.width;

            this.canvas.height =
                size.height;

            ctx.drawImage(
                layers[this.wiggleFrame],
                0,
                0
            );
        } else {
            this.canvas.width =
                size.width;

            this.canvas.height =
                size.height;

            const leftCtx =
                layers[0].getContext(
                    '2d',
                    { willReadFrequently: true }
                );

            const rightCtx =
                layers[1].getContext(
                    '2d',
                    { willReadFrequently: true }
                );

            const left =
                leftCtx.getImageData(
                    0,
                    0,
                    size.width,
                    size.height
                );

            const right =
                rightCtx.getImageData(
                    0,
                    0,
                    size.width,
                    size.height
                );

            const output =
                ctx.createImageData(
                    size.width,
                    size.height
                );

            for (
                let i = 0;
                i < output.data.length;
                i += 4
            ) {
                output.data[i] =
                    left.data[i];

                output.data[i + 1] =
                    right.data[i + 1];

                output.data[i + 2] =
                    right.data[i + 2];

                output.data[i + 3] = 255;
            }

            ctx.putImageData(
                output,
                0,
                0
            );
        }

        const sizeText =
            this.root.querySelector(
                '[data-gallery-size]'
            );

        if (sizeText) {
            sizeText.textContent =
                `${this.canvas.width}`
                + ` × `
                + `${this.canvas.height} px`;
        }
    }

    updateWiggle() {
        if (this.wiggleTimer) {
            window.clearInterval(
                this.wiggleTimer
            );

            this.wiggleTimer = null;
        }

        this.wiggleFrame = 0;

        if (
            this.mode() !== 'wiggle'
            || !this.left
            || !this.right
        ) {
            return;
        }

        this.wiggleTimer =
            window.setInterval(
                () => {
                    this.wiggleFrame =
                        this.wiggleFrame
                            === 0
                            ? 1
                            : 0;

                    this.render();
                },
                650
            );
    }

    setStatus(text) {
        const status =
            this.root.querySelector(
                '[data-gallery-status]'
            );

        if (status) {
            status.textContent = text;
        }
    }
}

const initializeCommunityGallery = () => {
    document.querySelectorAll(
        '[data-community-viewer]'
    ).forEach((root) => {
        new CommunityStereoViewer(root);
    });

    document.querySelectorAll(
        '[data-gallery-rating]'
    ).forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = event.submitter;

            if (!button || form.dataset.rated === 'true') {
                return;
            }

            const body = new FormData(form);
            body.set('rating', button.value);

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.content || '',
                },
                body,
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const panel = form.closest(
                '[data-gallery-rating-panel]'
            );

            form.dataset.rated = 'true';
            form.querySelectorAll('button').forEach((star) => {
                star.disabled = true;
                star.classList.add('is-muted');
            });

            panel?.querySelectorAll(
                '[data-gallery-rating-summary]'
            ).forEach((summary) => {
                summary.textContent = data.summary;
            });

            panel?.closest('[data-community-viewer]')
                ?.querySelector('[data-gallery-status]')
                ?.replaceChildren(data.summary);

            const activeItem = form.closest('[data-gallery-browser]')
                ?.querySelector(
                    '.community-gallery-strip-item.is-active'
                );

            activeItem?.setAttribute(
                'data-rating-summary',
                data.summary
            );
            activeItem?.setAttribute('data-rated', 'true');
        });
    });

    document.querySelectorAll(
        '[data-gallery-browser]'
    ).forEach((browser) => {
        const viewerRoot = browser.querySelector(
            '[data-community-viewer]'
        );
        const viewer = viewerRoot?.communityViewer;
        const items = Array.from(
            browser.querySelectorAll('[data-gallery-browser-item]')
        );
        const previous = browser.querySelector(
            '[data-gallery-nav="previous"]'
        );
        const next = browser.querySelector(
            '[data-gallery-nav="next"]'
        );
        let activeIndex = Math.max(
            0,
            items.findIndex((item) =>
                item.classList.contains('is-active')
            )
        );

        const updateRatingForm = (item) => {
            const form = browser.querySelector(
                '[data-gallery-rating]'
            );

            if (!form) {
                return;
            }

            const rated = item.dataset.rated === 'true';

            form.action = item.dataset.ratingUrl || form.action;
            form.dataset.rated = rated ? 'true' : 'false';
            form.querySelectorAll('button').forEach((star) => {
                star.disabled = rated;
                star.classList.toggle('is-muted', rated);
            });
        };

        const updateNavigation = () => {
            if (previous) {
                previous.hidden = activeIndex === 0;
                previous.href = items[activeIndex - 1]?.href || '#';
            }

            if (next) {
                next.hidden = activeIndex >= items.length - 1;
                next.href = items[activeIndex + 1]?.href || '#';
            }
        };

        const activate = async (index, pushHistory = true) => {
            const item = items[index];

            if (!item || index === activeIndex) {
                return;
            }

            activeIndex = index;

            items.forEach((thumbnail) => {
                thumbnail.classList.toggle(
                    'is-active',
                    thumbnail === item
                );
            });

            browser.querySelector(
                '[data-gallery-current-title]'
            ).textContent = item.dataset.title || '';

            const author = browser.querySelector(
                '[data-gallery-current-author]'
            );

            if (author) {
                author.textContent = item.dataset.author || '';
                author.href = item.dataset.authorUrl || '#';
            }

            browser.querySelectorAll(
                '[data-gallery-rating-summary]'
            ).forEach((summary) => {
                summary.textContent = item.dataset.ratingSummary || '0 ★ 0.0';
            });

            updateRatingForm(item);
            updateNavigation();

            if (pushHistory) {
                window.history.pushState(
                    {},
                    '',
                    item.href
                );
            }

            await viewer?.updateImages(
                item.dataset.leftUrl,
                item.dataset.rightUrl,
                item.dataset.ratingSummary || ''
            );
        };

        items.forEach((item, index) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();
                activate(index);
            });
        });

        previous?.addEventListener('click', (event) => {
            event.preventDefault();
            activate(activeIndex - 1);
        });

        next?.addEventListener('click', (event) => {
            event.preventDefault();
            activate(activeIndex + 1);
        });

        updateNavigation();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeCommunityGallery
    );
} else {
    initializeCommunityGallery();
}
