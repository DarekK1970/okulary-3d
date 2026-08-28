import {
    decodeImageBlob,
    downloadBlob,
    drawCover,
    splitJpegImages,
} from './mpo-utils';
import { encodeGif } from './gif-encoder';

class WigglegramMaker {
    constructor(root) {
        this.root = root;
        this.frames = [];
        this.previewCanvas = root.querySelector('[data-wiggle-canvas]');
        this.timer = null;
        this.frameIndex = 0;
        this.direction = 1;

        this.bindFiles();
        this.bindControls();
        this.bindActions();
        this.updateMetrics();
    }

    bindFiles() {
        const input = this.root.querySelector('[data-wiggle-files]');
        const dropzone = this.root.querySelector('[data-wiggle-dropzone]');

        input?.addEventListener('change', async () => {
            await this.loadFiles(input.files);
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
            await this.loadFiles(event.dataTransfer?.files || []);
        });
    }

    bindControls() {
        this.root.querySelectorAll('[data-wiggle-control]').forEach((control) => {
            control.addEventListener('change', () => {
                this.updateMetrics();
                this.restartPreview();
            });
            control.addEventListener('input', () => this.updateMetrics());
        });
    }

    bindActions() {
        this.root.querySelectorAll('[data-wiggle-action]').forEach((button) => {
            button.addEventListener('click', async () => {
                const action = button.dataset.wiggleAction;

                if (action === 'gif') {
                    await this.exportGif();
                } else if (action === 'frame-png') {
                    this.exportCurrentFrame();
                } else if (action === 'reset') {
                    this.reset();
                }
            });
        });
    }

    async loadFiles(fileList) {
        const files = Array.from(fileList || []).slice(0, 12);
        if (!files.length) return;

        this.setStatus(this.root.dataset.loading || 'Loading…');
        this.closeFrames();
        this.frames = [];

        for (const file of files) {
            try {
                const extension = file.name.split('.').pop()?.toLowerCase();

                if (extension === 'mpo') {
                    const blobs = splitJpegImages(await file.arrayBuffer());

                    for (const blob of blobs) {
                        if (this.frames.length >= 12) break;
                        this.frames.push({
                            bitmap: await decodeImageBlob(blob),
                            name: `${file.name} / ${this.frames.length + 1}`,
                        });
                    }
                } else if (file.type.startsWith('image/')) {
                    this.frames.push({
                        bitmap: await decodeImageBlob(file),
                        name: file.name,
                    });
                }
            } catch (error) {
                console.error(error);
            }

            if (this.frames.length >= 12) break;
        }

        this.updateFileList();
        this.updateMetrics();

        if (this.frames.length >= 2) {
            this.root.querySelector('[data-wiggle-empty]')?.classList.add('is-hidden');
            this.setStatus(this.root.dataset.ready || 'Ready');
            this.restartPreview();
        } else {
            this.setStatus(this.root.dataset.waiting || 'Add at least 2 frames');
        }
    }

    closeFrames() {
        this.frames.forEach((frame) => {
            if (typeof frame.bitmap.close === 'function') frame.bitmap.close();
        });
    }

    reset() {
        this.stopPreview();
        this.closeFrames();
        this.frames = [];
        this.frameIndex = 0;
        this.direction = 1;

        const input = this.root.querySelector('[data-wiggle-files]');
        if (input) input.value = '';

        this.previewCanvas.width = 1;
        this.previewCanvas.height = 1;
        this.root.querySelector('[data-wiggle-empty]')?.classList.remove('is-hidden');
        this.updateFileList();
        this.updateMetrics();
        this.setStatus(this.root.dataset.waiting || 'Add at least 2 frames');
    }

    updateFileList() {
        const list = this.root.querySelector('[data-wiggle-file-list]');
        if (!list) return;

        list.textContent = this.frames.length
            ? this.frames.map((frame, index) => `${index + 1}. ${frame.name}`).join(' · ')
            : '—';
    }

    numberControl(name, fallback) {
        const value = Number(
            this.root.querySelector(`[data-wiggle-control="${name}"]`)?.value
        );
        return Number.isFinite(value) ? value : fallback;
    }

    stringControl(name, fallback) {
        return this.root.querySelector(`[data-wiggle-control="${name}"]`)?.value || fallback;
    }

    updateMetrics() {
        const count = this.root.querySelector('[data-wiggle-metric="frames"]');
        const duration = this.root.querySelector('[data-wiggle-metric="duration"]');
        const size = this.root.querySelector('[data-wiggle-metric="size"]');

        if (count) count.textContent = String(this.frames.length);
        if (duration) duration.textContent = `${this.numberControl('delay', 140)} ms`;
        if (size) size.textContent = `${this.numberControl('width', 720)} px`;
    }

    getFrameOrder() {
        const order = Array.from({ length: this.frames.length }, (_, index) => index);

        if (this.stringControl('loopMode', 'pingpong') === 'pingpong' && order.length > 2) {
            return [...order, ...order.slice(1, -1).reverse()];
        }

        return order;
    }

    outputDimensions() {
        const first = this.frames[0]?.bitmap;
        if (!first) return { width: 1, height: 1 };

        const width = Math.max(240, Math.min(960, this.numberControl('width', 720)));
        return {
            width,
            height: Math.max(1, Math.round(width * first.height / first.width)),
        };
    }

    drawFrame(canvas, frameIndex) {
        const frame = this.frames[frameIndex];
        if (!frame) return;

        const dimensions = this.outputDimensions();
        canvas.width = dimensions.width;
        canvas.height = dimensions.height;
        drawCover(
            canvas.getContext('2d'),
            frame.bitmap,
            dimensions.width,
            dimensions.height
        );
    }

    restartPreview() {
        this.stopPreview();
        if (this.frames.length < 2) return;

        this.frameIndex = 0;
        this.direction = 1;
        this.drawFrame(this.previewCanvas, this.frameIndex);

        this.timer = window.setInterval(
            () => this.advancePreview(),
            Math.max(60, this.numberControl('delay', 140))
        );
    }

    advancePreview() {
        const mode = this.stringControl('loopMode', 'pingpong');

        if (mode === 'pingpong') {
            this.frameIndex += this.direction;

            if (this.frameIndex >= this.frames.length - 1) {
                this.frameIndex = this.frames.length - 1;
                this.direction = -1;
            } else if (this.frameIndex <= 0) {
                this.frameIndex = 0;
                this.direction = 1;
            }
        } else {
            this.frameIndex = (this.frameIndex + 1) % this.frames.length;
        }

        this.drawFrame(this.previewCanvas, this.frameIndex);
    }

    stopPreview() {
        if (this.timer) {
            window.clearInterval(this.timer);
            this.timer = null;
        }
    }

    async exportGif() {
        if (this.frames.length < 2) {
            this.setStatus(this.root.dataset.waiting || 'Add at least 2 frames');
            return;
        }

        this.setStatus(this.root.dataset.exporting || 'Encoding GIF…');
        this.stopPreview();

        const dimensions = this.outputDimensions();
        const order = this.getFrameOrder();
        const rgbaFrames = [];
        const canvas = document.createElement('canvas');
        canvas.width = dimensions.width;
        canvas.height = dimensions.height;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });

        for (const index of order) {
            ctx.clearRect(0, 0, dimensions.width, dimensions.height);
            drawCover(
                ctx,
                this.frames[index].bitmap,
                dimensions.width,
                dimensions.height
            );
            rgbaFrames.push(
                ctx.getImageData(0, 0, dimensions.width, dimensions.height).data
            );
            await new Promise((resolve) => requestAnimationFrame(resolve));
        }

        const gif = encodeGif({
            width: dimensions.width,
            height: dimensions.height,
            frames: rgbaFrames,
            delayMs: this.numberControl('delay', 140),
            loop: true,
        });

        downloadBlob(
            gif,
            `wigglegram-${new Date().toISOString().slice(0, 10)}.gif`
        );

        this.setStatus(this.root.dataset.ready || 'Ready');
        this.restartPreview();
    }

    exportCurrentFrame() {
        if (this.frames.length < 1) return;

        const canvas = document.createElement('canvas');
        this.drawFrame(canvas, this.frameIndex);

        canvas.toBlob((blob) => {
            if (!blob) return;
            downloadBlob(blob, `wiggle-frame-${this.frameIndex + 1}.png`);
        }, 'image/png');
    }

    setStatus(text) {
        const status = this.root.querySelector('[data-wiggle-status]');
        if (status) status.textContent = text;
    }
}

const initializeWigglegram = () => {
    document.querySelectorAll('[data-wigglegram]').forEach((root) => {
        new WigglegramMaker(root);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeWigglegram);
} else {
    initializeWigglegram();
}
