import {
    decodeImageBlob,
    downloadBlob,
    drawCover,
    splitJpegImages,
} from './mpo-utils';

class MpoViewer {
    constructor(root) {
        this.root = root;
        this.blobs = [];
        this.images = [];
        this.canvas = root.querySelector('[data-mpo-canvas]');
        this.status = root.querySelector('[data-mpo-status]');

        this.bindFile();
        this.bindActions();
        this.bindMode();
    }

    bindFile() {
        const input = this.root.querySelector('[data-mpo-file]');
        const dropzone = this.root.querySelector('[data-mpo-dropzone]');

        input?.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (file) await this.load(file);
        });

        if (!dropzone) return;

        ['dragenter', 'dragover'].forEach((name) => {
            dropzone.addEventListener(name, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((name) => {
            dropzone.addEventListener(name, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', async (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (file) await this.load(file);
        });
    }

    bindActions() {
        this.root.querySelectorAll('[data-mpo-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.mpoAction;

                if (action === 'left-jpeg') {
                    this.exportJpeg(0, 'left');
                } else if (action === 'right-jpeg') {
                    this.exportJpeg(1, 'right');
                } else if (action === 'sbs-png') {
                    this.exportCanvas('parallel');
                } else if (action === 'anaglyph-png') {
                    this.exportCanvas('anaglyph');
                }
            });
        });
    }

    bindMode() {
        this.root.querySelector('[data-mpo-mode]')?.addEventListener('change', () => {
            this.renderPreview();
        });
    }

    async load(file) {
        this.setStatus(this.root.dataset.loading || 'Reading MPO…');

        try {
            const blobs = splitJpegImages(await file.arrayBuffer());

            if (blobs.length < 2) {
                this.setStatus(this.root.dataset.invalid || 'No stereo pair found.');
                return;
            }

            this.closeImages();
            this.blobs = blobs.slice(0, 12);
            this.images = [];

            for (const blob of this.blobs) {
                this.images.push(await decodeImageBlob(blob));
            }

            this.updateInfo(file.name);
            this.renderPreview();
            this.root.querySelector('[data-mpo-empty]')?.classList.add('is-hidden');
            this.setStatus(this.root.dataset.ready || 'Ready');
        } catch (error) {
            console.error(error);
            this.setStatus(this.root.dataset.invalid || 'Invalid MPO file.');
        }
    }

    closeImages() {
        this.images.forEach((image) => {
            if (typeof image.close === 'function') image.close();
        });
    }

    updateInfo(filename) {
        const file = this.root.querySelector('[data-mpo-filename]');
        const count = this.root.querySelector('[data-mpo-count]');
        const dimensions = this.root.querySelector('[data-mpo-dimensions]');

        if (file) file.textContent = filename;
        if (count) count.textContent = String(this.images.length);
        if (dimensions && this.images[0]) {
            dimensions.textContent = `${this.images[0].width} × ${this.images[0].height} px`;
        }
    }

    frameSize(full = false) {
        const left = this.images[0];
        const right = this.images[1];
        const sourceWidth = Math.min(left.width, right.width);
        const sourceHeight = Math.min(left.height, right.height);

        if (full) return { width: sourceWidth, height: sourceHeight };

        const scale = Math.min(1, 1400 / Math.max(sourceWidth, sourceHeight));

        return {
            width: Math.max(1, Math.round(sourceWidth * scale)),
            height: Math.max(1, Math.round(sourceHeight * scale)),
        };
    }

    alignedCanvases(width, height) {
        return [0, 1].map((index) => {
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            drawCover(canvas.getContext('2d'), this.images[index], width, height);
            return canvas;
        });
    }

    renderToCanvas(canvas, mode, full = false) {
        if (this.images.length < 2) return;

        const { width, height } = this.frameSize(full);
        const pair = this.alignedCanvases(width, height);

        if (mode === 'parallel' || mode === 'cross') {
            canvas.width = width * 2;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            const first = mode === 'cross' ? pair[1] : pair[0];
            const second = mode === 'cross' ? pair[0] : pair[1];
            ctx.drawImage(first, 0, 0);
            ctx.drawImage(second, width, 0);
            return;
        }

        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d', { willReadFrequently: mode === 'anaglyph' });

        if (mode === 'left') {
            ctx.drawImage(pair[0], 0, 0);
            return;
        }

        if (mode === 'right') {
            ctx.drawImage(pair[1], 0, 0);
            return;
        }

        const left = pair[0].getContext('2d', { willReadFrequently: true })
            .getImageData(0, 0, width, height);
        const right = pair[1].getContext('2d', { willReadFrequently: true })
            .getImageData(0, 0, width, height);
        const output = ctx.createImageData(width, height);

        for (let i = 0; i < output.data.length; i += 4) {
            output.data[i] = left.data[i];
            output.data[i + 1] = right.data[i + 1];
            output.data[i + 2] = right.data[i + 2];
            output.data[i + 3] = 255;
        }

        ctx.putImageData(output, 0, 0);
    }

    renderPreview() {
        if (this.images.length < 2) return;

        const mode = this.root.querySelector('[data-mpo-mode]')?.value || 'parallel';
        this.renderToCanvas(this.canvas, mode, false);

        const size = this.root.querySelector('[data-mpo-preview-size]');
        if (size) size.textContent = `${this.canvas.width} × ${this.canvas.height} px`;
    }

    exportJpeg(index, side) {
        const blob = this.blobs[index];
        if (!blob) return;

        downloadBlob(
            blob,
            `mpo-${side}-${new Date().toISOString().slice(0, 10)}.jpg`
        );
    }

    exportCanvas(mode) {
        if (this.images.length < 2) return;

        const canvas = document.createElement('canvas');
        this.renderToCanvas(canvas, mode, true);

        canvas.toBlob((blob) => {
            if (!blob) return;
            downloadBlob(
                blob,
                `mpo-${mode}-${new Date().toISOString().slice(0, 10)}.png`
            );
        }, 'image/png');
    }

    setStatus(text) {
        if (this.status) this.status.textContent = text;
    }
}

const initializeMpoViewer = () => {
    document.querySelectorAll('[data-mpo-viewer]').forEach((root) => {
        new MpoViewer(root);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMpoViewer);
} else {
    initializeMpoViewer();
}
