import {
    drawCover,
} from './mpo-utils';

class ArchiveStereoViewer {
    constructor(root) {
        this.root = root;
        this.canvas = root.querySelector(
            '[data-archive-canvas]'
        );
        this.original = null;
        this.left = null;
        this.right = null;
        this.swapped = false;
        this.wiggleFrame = 0;
        this.wiggleTimer = null;

        this.bind();
        this.load();
    }

    bind() {
        this.root.querySelector(
            '[data-archive-mode]'
        )?.addEventListener('change', () => {
            this.updateWiggle();
            this.render();
        });

        this.root.querySelector(
            '[data-archive-action="swap"]'
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
            this.original = await this.loadImage(
                this.root.dataset.originalUrl
            );

            if (
                this.root.dataset.leftUrl
                && this.root.dataset.rightUrl
            ) {
                [this.left, this.right] =
                    await Promise.all([
                        this.loadImage(
                            this.root.dataset.leftUrl
                        ),
                        this.loadImage(
                            this.root.dataset.rightUrl
                        ),
                    ]);
            }

            this.root.querySelector(
                '[data-archive-empty]'
            )?.classList.add('is-hidden');

            this.setStatus(
                this.root.dataset.ready || 'Ready'
            );

            this.render();
        } catch (error) {
            console.error(error);

            this.setStatus(
                this.root.dataset.error
                || 'Image loading failed.'
            );
        }
    }

    mode() {
        return (
            this.root.querySelector(
                '[data-archive-mode]'
            )?.value
            || 'original'
        );
    }

    stereoImages() {
        if (!this.left || !this.right) {
            return [null, null];
        }

        return this.swapped
            ? [this.right, this.left]
            : [this.left, this.right];
    }

    previewSize(image, pair = false) {
        const multiplier = pair ? 2 : 1;

        const scale = Math.min(
            1,
            1600 / Math.max(
                image.width * multiplier,
                image.height
            )
        );

        return {
            width: Math.max(
                1,
                Math.round(
                    image.width * scale
                )
            ),
            height: Math.max(
                1,
                Math.round(
                    image.height * scale
                )
            ),
        };
    }

    createLayer(
        image,
        width,
        height
    ) {
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
    }

    render() {
        if (!this.original) {
            return;
        }

        const mode = this.mode();

        if (
            mode === 'original'
            || !this.left
            || !this.right
        ) {
            const size =
                this.previewSize(
                    this.original,
                    false
                );

            this.canvas.width =
                size.width;

            this.canvas.height =
                size.height;

            drawCover(
                this.canvas.getContext('2d'),
                this.original,
                size.width,
                size.height
            );

            this.updateSize();
            return;
        }

        const [left, right] =
            this.stereoImages();

        const sourceWidth =
            Math.min(
                left.width,
                right.width
            );

        const sourceHeight =
            Math.min(
                left.height,
                right.height
            );

        const scale = Math.min(
            1,
            1600 / Math.max(
                ['parallel', 'cross']
                    .includes(mode)
                    ? sourceWidth * 2
                    : sourceWidth,
                sourceHeight
            )
        );

        const width = Math.max(
            1,
            Math.round(
                sourceWidth * scale
            )
        );

        const height = Math.max(
            1,
            Math.round(
                sourceHeight * scale
            )
        );

        const leftLayer =
            this.createLayer(
                left,
                width,
                height
            );

        const rightLayer =
            this.createLayer(
                right,
                width,
                height
            );

        const ctx =
            this.canvas.getContext(
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
                width * 2;

            this.canvas.height =
                height;

            const first =
                mode === 'cross'
                    ? rightLayer
                    : leftLayer;

            const second =
                mode === 'cross'
                    ? leftLayer
                    : rightLayer;

            ctx.drawImage(first, 0, 0);
            ctx.drawImage(
                second,
                width,
                0
            );
        } else if (mode === 'wiggle') {
            this.canvas.width =
                width;

            this.canvas.height =
                height;

            ctx.drawImage(
                this.wiggleFrame === 0
                    ? leftLayer
                    : rightLayer,
                0,
                0
            );
        } else {
            this.canvas.width =
                width;

            this.canvas.height =
                height;

            const leftCtx =
                leftLayer.getContext(
                    '2d',
                    { willReadFrequently: true }
                );

            const rightCtx =
                rightLayer.getContext(
                    '2d',
                    { willReadFrequently: true }
                );

            const leftData =
                leftCtx.getImageData(
                    0,
                    0,
                    width,
                    height
                );

            const rightData =
                rightCtx.getImageData(
                    0,
                    0,
                    width,
                    height
                );

            const output =
                ctx.createImageData(
                    width,
                    height
                );

            for (
                let i = 0;
                i < output.data.length;
                i += 4
            ) {
                output.data[i] =
                    leftData.data[i];

                output.data[i + 1] =
                    rightData.data[i + 1];

                output.data[i + 2] =
                    rightData.data[i + 2];

                output.data[i + 3] = 255;
            }

            ctx.putImageData(
                output,
                0,
                0
            );
        }

        this.updateSize();
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

    updateSize() {
        const size =
            this.root.querySelector(
                '[data-archive-size]'
            );

        if (size) {
            size.textContent =
                `${this.canvas.width}`
                + ` × `
                + `${this.canvas.height} px`;
        }
    }

    setStatus(text) {
        const status =
            this.root.querySelector(
                '[data-archive-status]'
            );

        if (status) {
            status.textContent = text;
        }
    }
}

const initializeArchiveViewer = () => {
    document.querySelectorAll(
        '[data-archive-viewer]'
    ).forEach((root) => {
        new ArchiveStereoViewer(root);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeArchiveViewer
    );
} else {
    initializeArchiveViewer();
}
