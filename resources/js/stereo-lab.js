class StereoLab {
    constructor(root) {
        this.root = root;
        this.tool = root.dataset.tool || 'anaglyph';

        this.left = null;
        this.right = null;

        this.previewCanvas = root.querySelector(
            '[data-preview-canvas]'
        );
        this.emptyState = root.querySelector('[data-empty-state]');
        this.status = root.querySelector('[data-status]');
        this.imageInfo = root.querySelector('[data-image-info]');

        this.controls = {
            shiftX: root.querySelector('[data-control="shiftX"]'),
            shiftY: root.querySelector('[data-control="shiftY"]'),
            scale: root.querySelector('[data-control="scale"]'),
            rotation: root.querySelector('[data-control="rotation"]'),
            anaglyphMode: root.querySelector(
                '[data-control="anaglyphMode"]'
            ),
            previewMode: root.querySelector(
                '[data-control="previewMode"]'
            ),
            exportSize: root.querySelector(
                '[data-control="exportSize"]'
            ),
        };

        this.outputs = {
            shiftX: root.querySelector('[data-output="shiftX"]'),
            shiftY: root.querySelector('[data-output="shiftY"]'),
            scale: root.querySelector('[data-output="scale"]'),
            rotation: root.querySelector('[data-output="rotation"]'),
        };

        this.blinkPhase = 'left';
        this.blinkTimer = null;
        this.renderQueued = false;

        this.bindFiles();
        this.bindControls();
        this.bindActions();
        this.updateOutputs();
        this.updateBlinkTimer();
    }

    bindFiles() {
        ['left', 'right'].forEach((side) => {
            const input = this.root.querySelector(
                `[data-file-input="${side}"]`
            );
            const dropzone = this.root.querySelector(
                `[data-dropzone="${side}"]`
            );

            input?.addEventListener('change', async () => {
                const file = input.files?.[0];

                if (file) {
                    await this.loadFile(side, file);
                }
            });

            if (!dropzone) {
                return;
            }

            ['dragenter', 'dragover'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragover');
                });
            });

            dropzone.addEventListener('drop', async (event) => {
                const file = event.dataTransfer?.files?.[0];

                if (file) {
                    await this.loadFile(side, file);
                }
            });
        });
    }

    bindControls() {
        Object.values(this.controls).forEach((control) => {
            if (!control) {
                return;
            }

            control.addEventListener('input', () => {
                this.updateOutputs();
                this.updateBlinkTimer();
                this.queueRender();
            });

            control.addEventListener('change', () => {
                this.updateOutputs();
                this.updateBlinkTimer();
                this.queueRender();
            });
        });
    }

    bindActions() {
        this.root.querySelectorAll('[data-action]').forEach((button) => {
            button.addEventListener('click', async () => {
                const action = button.dataset.action;

                if (action === 'swap') {
                    this.swapImages();
                } else if (action === 'reset') {
                    this.resetGeometry();
                } else if (action === 'fit') {
                    this.fitRightImage();
                } else if (action === 'export') {
                    await this.exportPng();
                }
            });
        });
    }

    async loadFile(side, file) {
        if (!file.type.startsWith('image/')) {
            this.setStatus(
                this.root.dataset.errorImage || 'Invalid image'
            );
            return;
        }

        this.setStatus(
            this.root.dataset.loading || 'Loading…'
        );

        try {
            const image = await this.decodeImage(file);
            const previous = this[side];

            if (
                previous?.bitmap
                && typeof previous.bitmap.close === 'function'
            ) {
                previous.bitmap.close();
            }

            this[side] = {
                bitmap: image,
                width: image.width,
                height: image.height,
                name: file.name,
            };

            this.updateFileLabel(side);
            this.setStatus(
                this.root.dataset.ready || 'Ready'
            );
            this.emptyState?.classList.toggle(
                'is-hidden',
                Boolean(this.left && this.right)
            );

            if (this.left && this.right) {
                this.fitRightImage();
                this.updateBlinkTimer();
            } else {
                this.queueRender();
            }
        } catch (error) {
            console.error(error);
            this.setStatus(
                this.root.dataset.errorImage || 'Invalid image'
            );
        }
    }

    async decodeImage(file) {
        if ('createImageBitmap' in window) {
            return await createImageBitmap(
                file,
                { imageOrientation: 'from-image' }
            );
        }

        return await new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const image = new Image();

            image.onload = () => {
                URL.revokeObjectURL(url);
                resolve(image);
            };

            image.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('Image decode failed'));
            };

            image.src = url;
        });
    }

    updateFileLabel(side) {
        const label = this.root.querySelector(
            `[data-file-name="${side}"]`
        );
        const zone = this.root.querySelector(
            `[data-dropzone="${side}"]`
        );
        const image = this[side];

        if (label && image) {
            label.textContent =
                `${image.name} · ${image.width}×${image.height}`;
        }

        zone?.classList.toggle('has-file', Boolean(image));
    }

    updateOutputs() {
        const shiftX = this.value('shiftX', 0);
        const shiftY = this.value('shiftY', 0);
        const scale = this.value('scale', 100);
        const rotation = this.value('rotation', 0);

        if (this.outputs.shiftX) {
            this.outputs.shiftX.textContent =
                `${this.pretty(shiftX)} px`;
        }

        if (this.outputs.shiftY) {
            this.outputs.shiftY.textContent =
                `${this.pretty(shiftY)} px`;
        }

        if (this.outputs.scale) {
            this.outputs.scale.textContent =
                `${this.pretty(scale)}%`;
        }

        if (this.outputs.rotation) {
            this.outputs.rotation.textContent =
                `${this.pretty(rotation)}°`;
        }
    }

    swapImages() {
        [this.left, this.right] = [this.right, this.left];

        this.updateFileLabel('left');
        this.updateFileLabel('right');
        this.queueRender();
    }

    resetGeometry() {
        this.setControl('shiftX', 0);
        this.setControl('shiftY', 0);
        this.setControl('scale', 100);
        this.setControl('rotation', 0);

        this.updateOutputs();
        this.queueRender();
    }

    fitRightImage() {
        if (!this.left || !this.right) {
            this.resetGeometry();
            return;
        }

        this.setControl('shiftX', 0);
        this.setControl('shiftY', 0);
        this.setControl('scale', 100);
        this.setControl('rotation', 0);

        this.updateOutputs();
        this.queueRender();
    }

    setControl(name, value) {
        if (this.controls[name]) {
            this.controls[name].value = String(value);
        }
    }

    value(name, fallback = 0) {
        const control = this.controls[name];

        if (!control) {
            return fallback;
        }

        const number = Number(control.value);

        return Number.isFinite(number) ? number : fallback;
    }

    pretty(value) {
        const rounded = Math.round(value * 100) / 100;

        return Number.isInteger(rounded)
            ? String(rounded)
            : rounded.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    }

    currentMode() {
        if (this.tool === 'anaglyph') {
            return 'anaglyph';
        }

        return this.controls.previewMode?.value || 'parallel';
    }

    queueRender() {
        if (this.renderQueued) {
            return;
        }

        this.renderQueued = true;

        requestAnimationFrame(() => {
            this.renderQueued = false;
            this.renderPreview();
        });
    }

    renderPreview() {
        if (!this.left || !this.right || !this.previewCanvas) {
            return;
        }

        const mode = this.currentMode();

        this.renderToCanvas(
            this.previewCanvas,
            mode,
            1600,
            true
        );

        this.emptyState?.classList.add('is-hidden');

        if (this.imageInfo) {
            this.imageInfo.textContent =
                `${this.previewCanvas.width} × ${this.previewCanvas.height} px`;
        }

        this.setStatus(
            this.root.dataset.ready || 'Ready'
        );
    }

    renderToCanvas(
        target,
        mode,
        maxDimension,
        guides = false
    ) {
        const size = this.resolveFrameSize(
            mode,
            maxDimension
        );

        const layers = this.createAlignedLayers(
            size.frameWidth,
            size.frameHeight
        );

        target.width = size.outputWidth;
        target.height = size.outputHeight;

        const ctx = target.getContext('2d', {
            willReadFrequently: mode === 'anaglyph',
        });

        ctx.clearRect(0, 0, target.width, target.height);

        if (mode === 'anaglyph') {
            this.drawAnaglyph(
                ctx,
                layers.left,
                layers.right,
                size.frameWidth,
                size.frameHeight
            );
        } else if (mode === 'overlay') {
            ctx.globalAlpha = 1;
            ctx.drawImage(layers.left, 0, 0);
            ctx.globalAlpha = .5;
            ctx.drawImage(layers.right, 0, 0);
            ctx.globalAlpha = 1;
        } else if (mode === 'blink') {
            const layer = this.blinkPhase === 'left'
                ? layers.left
                : layers.right;

            ctx.drawImage(layer, 0, 0);
        } else {
            const first = mode === 'cross'
                ? layers.right
                : layers.left;
            const second = mode === 'cross'
                ? layers.left
                : layers.right;

            ctx.drawImage(first, 0, 0);
            ctx.drawImage(second, size.frameWidth, 0);
        }

        if (guides && this.tool === 'alignment') {
            this.drawGuides(ctx, target.width, target.height);
        }
    }

    resolveFrameSize(mode, maxDimension) {
        const sourceWidth = Math.max(
            1,
            Math.min(this.left.width, this.right.width)
        );
        const sourceHeight = Math.max(
            1,
            Math.min(this.left.height, this.right.height)
        );

        const isPair = ['parallel', 'cross'].includes(mode);
        const sourceOutputWidth = isPair
            ? sourceWidth * 2
            : sourceWidth;
        const sourceOutputHeight = sourceHeight;

        let scale = 1;

        if (maxDimension !== 'original') {
            const cap = Math.max(320, Number(maxDimension) || 1600);

            scale = Math.min(
                1,
                cap / Math.max(
                    sourceOutputWidth,
                    sourceOutputHeight
                )
            );
        }

        const frameWidth = Math.max(
            1,
            Math.round(sourceWidth * scale)
        );
        const frameHeight = Math.max(
            1,
            Math.round(sourceHeight * scale)
        );

        return {
            frameWidth,
            frameHeight,
            outputWidth: isPair
                ? frameWidth * 2
                : frameWidth,
            outputHeight: frameHeight,
        };
    }

    createAlignedLayers(width, height) {
        const leftCanvas = document.createElement('canvas');
        const rightCanvas = document.createElement('canvas');

        leftCanvas.width = width;
        leftCanvas.height = height;
        rightCanvas.width = width;
        rightCanvas.height = height;

        const leftCtx = leftCanvas.getContext('2d');
        const rightCtx = rightCanvas.getContext('2d');

        this.drawCover(
            leftCtx,
            this.left.bitmap,
            width,
            height
        );

        this.drawTransformedRight(
            rightCtx,
            this.right.bitmap,
            width,
            height
        );

        return {
            left: leftCanvas,
            right: rightCanvas,
        };
    }

    drawCover(ctx, image, width, height) {
        const sourceRatio = image.width / image.height;
        const targetRatio = width / height;

        let drawWidth;
        let drawHeight;

        if (sourceRatio > targetRatio) {
            drawHeight = height;
            drawWidth = height * sourceRatio;
        } else {
            drawWidth = width;
            drawHeight = width / sourceRatio;
        }

        const x = (width - drawWidth) / 2;
        const y = (height - drawHeight) / 2;

        ctx.drawImage(
            image,
            x,
            y,
            drawWidth,
            drawHeight
        );
    }

    drawTransformedRight(
        ctx,
        image,
        width,
        height
    ) {
        const sourceBaseWidth = Math.max(
            1,
            Math.min(this.left.width, this.right.width)
        );
        const pixelScale = width / sourceBaseWidth;

        const shiftX = this.value('shiftX', 0) * pixelScale;
        const shiftY = this.value('shiftY', 0) * pixelScale;
        const scale = this.value('scale', 100) / 100;
        const rotation =
            this.value('rotation', 0) * Math.PI / 180;

        ctx.save();
        ctx.translate(
            width / 2 + shiftX,
            height / 2 + shiftY
        );
        ctx.rotate(rotation);
        ctx.scale(scale, scale);
        ctx.translate(-width / 2, -height / 2);

        this.drawCover(
            ctx,
            image,
            width,
            height
        );

        ctx.restore();
    }

    drawAnaglyph(
        targetCtx,
        leftCanvas,
        rightCanvas,
        width,
        height
    ) {
        const leftCtx = leftCanvas.getContext(
            '2d',
            { willReadFrequently: true }
        );
        const rightCtx = rightCanvas.getContext(
            '2d',
            { willReadFrequently: true }
        );

        const left = leftCtx.getImageData(
            0,
            0,
            width,
            height
        );
        const right = rightCtx.getImageData(
            0,
            0,
            width,
            height
        );

        const output = targetCtx.createImageData(
            width,
            height
        );

        const mode =
            this.controls.anaglyphMode?.value || 'color';

        for (let i = 0; i < output.data.length; i += 4) {
            const lr = left.data[i];
            const lg = left.data[i + 1];
            const lb = left.data[i + 2];

            const rr = right.data[i];
            const rg = right.data[i + 1];
            const rb = right.data[i + 2];

            const leftLum =
                .299 * lr + .587 * lg + .114 * lb;
            const rightLum =
                .299 * rr + .587 * rg + .114 * rb;

            if (mode === 'gray') {
                output.data[i] = leftLum;
                output.data[i + 1] = rightLum;
                output.data[i + 2] = rightLum;
            } else if (mode === 'half-color') {
                output.data[i] = leftLum;
                output.data[i + 1] = rg;
                output.data[i + 2] = rb;
            } else if (mode === 'optimized') {
                output.data[i] = .7 * lg + .3 * lb;
                output.data[i + 1] = rg;
                output.data[i + 2] = rb;
            } else {
                output.data[i] = lr;
                output.data[i + 1] = rg;
                output.data[i + 2] = rb;
            }

            output.data[i + 3] = Math.max(
                left.data[i + 3],
                right.data[i + 3]
            );
        }

        targetCtx.putImageData(output, 0, 0);
    }

    drawGuides(ctx, width, height) {
        ctx.save();
        ctx.lineWidth = 1;
        ctx.strokeStyle = 'rgba(255,255,255,.58)';
        ctx.setLineDash([6, 6]);

        [1 / 3, 1 / 2, 2 / 3].forEach((ratio) => {
            const y = Math.round(height * ratio);

            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(width, y);
            ctx.stroke();
        });

        ctx.restore();
    }

    updateBlinkTimer() {
        const shouldBlink =
            this.tool === 'alignment'
            && this.currentMode() === 'blink'
            && this.left
            && this.right;

        if (shouldBlink && !this.blinkTimer) {
            this.blinkTimer = window.setInterval(() => {
                this.blinkPhase =
                    this.blinkPhase === 'left'
                        ? 'right'
                        : 'left';
                this.queueRender();
            }, 650);
        }

        if (!shouldBlink && this.blinkTimer) {
            window.clearInterval(this.blinkTimer);
            this.blinkTimer = null;
            this.blinkPhase = 'left';
        }
    }

    async exportPng() {
        if (!this.left || !this.right) {
            this.setStatus(
                this.root.dataset.errorTwoImages
                || 'Select two images first'
            );
            return;
        }

        this.setStatus(
            this.root.dataset.loading || 'Rendering…'
        );

        await new Promise((resolve) => {
            requestAnimationFrame(resolve);
        });

        const requestedSize =
            this.controls.exportSize?.value || '2400';

        let mode = this.currentMode();

        if (
            this.tool === 'alignment'
            && ['overlay', 'blink'].includes(mode)
        ) {
            mode = 'parallel';
        }

        const exportCanvas = document.createElement('canvas');

        this.renderToCanvas(
            exportCanvas,
            mode,
            requestedSize,
            false
        );

        const blob = await new Promise((resolve) => {
            exportCanvas.toBlob(
                resolve,
                'image/png'
            );
        });

        if (!blob) {
            this.setStatus(
                this.root.dataset.errorImage || 'Export failed'
            );
            return;
        }

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const prefix =
            this.root.dataset.downloadPrefix || 'stereo';

        link.href = url;
        link.download =
            `${prefix}-${new Date().toISOString().slice(0, 10)}.png`;

        document.body.appendChild(link);
        link.click();
        link.remove();

        window.setTimeout(
            () => URL.revokeObjectURL(url),
            1000
        );

        this.setStatus(
            this.root.dataset.ready || 'Ready'
        );
    }

    setStatus(text) {
        if (this.status) {
            this.status.textContent = text;
        }
    }
}

const initializeStereoLabs = () => {
    document.querySelectorAll('[data-stereo-lab]').forEach((root) => {
        new StereoLab(root);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeStereoLabs
    );
} else {
    initializeStereoLabs();
}
