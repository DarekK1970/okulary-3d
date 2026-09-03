import {
    downloadCanvasAsPdf,
    downloadCanvasAsPng,
} from './pdf-export';

class LenticularLab {
    constructor(root) {
        this.root = root;
        this.images = [];
        this.interlaceCanvas = root.querySelector(
            '[data-interlacer-canvas]'
        );
        this.pitchCanvas = root.querySelector(
            '[data-pitch-canvas]'
        );

        this.bindFiles();
        this.bindControls();
        this.bindActions();

        this.updateFileList();
        this.updateAllCalculations();
        this.generatePitchTestPreview();
    }

    bindFiles() {
        const input = this.root.querySelector(
            '[data-lenticular-files]'
        );
        const dropzone = this.root.querySelector(
            '[data-lenticular-dropzone]'
        );

        input?.addEventListener('change', async () => {
            await this.loadFiles(input.files);
        });

        if (!dropzone) {
            return;
        }

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
            await this.loadFiles(
                event.dataTransfer?.files || []
            );
        });
    }

    bindControls() {
        this.root.querySelectorAll(
            [
                '[data-lenticular-control]',
                '[data-pitch-control]',
                '[data-calc-control]',
                '[data-wizard-control]',
            ].join(',')
        ).forEach((control) => {
            control.addEventListener('input', () => {
                this.updateAllCalculations();
            });

            control.addEventListener('change', () => {
                this.updateAllCalculations();
                if (control.dataset.lenticularControl === 'orientation' && this.images.length >= 2) {
                    this.renderInterlacedPreview();
                }
                if (control.dataset.pitchControl === 'orientation') {
                    this.generatePitchTestPreview();
                }
            });
        });
    }

    bindActions() {
        this.root.querySelectorAll('[data-action]')
            .forEach((button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        const action = button.dataset.action;

                        if (action === 'interlace-render') {
                            await this.renderInterlacedPreview();
                        } else if (action === 'interlace-export-png') {
                            await this.exportInterlacedPng();
                        } else if (action === 'interlace-export-pdf') {
                            await this.exportInterlacedPdf();
                        } else if (action === 'interlace-reset') {
                            this.resetViews();
                        } else if (action === 'pitch-generate') {
                            this.generatePitchTestPreview();
                        } else if (action === 'pitch-export-png') {
                            this.exportPitchPng();
                        } else if (action === 'pitch-export-pdf') {
                            this.exportPitchPdf();
                        } else if (action === 'wizard-apply') {
                            this.applyWizardToInterlacer();
                        }
                    }
                );
            });
    }

    async loadFiles(fileList) {
        const allFiles = Array.from(fileList || []);

        const files = allFiles
            .filter((file) => file.type.startsWith('image/'))
            .slice(0, 12);

        if (allFiles.length > 12) {
            this.setStatus(
                this.root.dataset.tooMany
                || 'Maximum 12 views.'
            );
        }

        if (files.length === 0) {
            return;
        }

        this.setStatus(
            this.root.dataset.processing
            || 'Processing…'
        );

        const loaded = [];

        for (const file of files) {
            try {
                const bitmap = await this.decodeImage(file);

                loaded.push({
                    bitmap,
                    name: file.name,
                    width: bitmap.width,
                    height: bitmap.height,
                });
            } catch (error) {
                console.error(error);
            }
        }

        this.closeBitmaps();
        this.images = loaded;

        this.updateFileList();
        this.updateAllCalculations();

        if (this.images.length >= 2) {
            await this.renderInterlacedPreview();
        } else {
            this.setStatus(
                this.root.dataset.waiting
                || 'Add at least 2 views'
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
                reject(
                    new Error('Image decode failed')
                );
            };

            image.src = url;
        });
    }

    closeBitmaps() {
        this.images.forEach((image) => {
            if (
                image.bitmap
                && typeof image.bitmap.close === 'function'
            ) {
                image.bitmap.close();
            }
        });
    }

    resetViews() {
        this.closeBitmaps();
        this.images = [];

        const fileInput = this.root.querySelector(
            '[data-lenticular-files]'
        );

        if (fileInput) {
            fileInput.value = '';
        }

        this.updateFileList();
        this.updateAllCalculations();

        if (this.interlaceCanvas) {
            this.interlaceCanvas.width = 1;
            this.interlaceCanvas.height = 1;
        }

        this.root.querySelector(
            '[data-interlacer-empty]'
        )?.classList.remove('is-hidden');

        const size = this.root.querySelector(
            '[data-interlacer-size]'
        );

        if (size) {
            size.textContent = '—';
        }

        this.setStatus(
            this.root.dataset.waiting
            || 'Add at least 2 views'
        );
    }

    updateFileList() {
        const list = this.root.querySelector(
            '[data-lenticular-file-list]'
        );
        const metric = this.root.querySelector(
            '[data-metric="views"]'
        );

        if (metric) {
            metric.textContent =
                String(this.images.length);
        }

        if (!list) {
            return;
        }

        if (!this.images.length) {
            list.textContent = '—';
            return;
        }

        list.textContent = this.images
            .map((image, index) => {
                return `${index + 1}. ${image.name}`;
            })
            .join(' · ');
    }

    getNumber(selector, fallback) {
        const element =
            this.root.querySelector(selector);
        const value = Number(element?.value);

        return Number.isFinite(value)
            ? value
            : fallback;
    }

    pretty(value) {
        const rounded =
            Math.round(value * 100) / 100;

        return Number.isInteger(rounded)
            ? String(rounded)
            : rounded
                .toFixed(2)
                .replace(/0+$/, '')
                .replace(/\.$/, '');
    }

    mmToPx(mm, dpi) {
        return Math.round((mm / 25.4) * dpi);
    }

    getInterlaceSettings() {
        const lpi = Math.max(
            20,
            this.getNumber(
                '[data-lenticular-control="lpi"]',
                60
            )
        );

        const dpi = Math.max(
            72,
            this.getNumber(
                '[data-lenticular-control="dpi"]',
                600
            )
        );

        const phase = Math.max(
            0,
            this.getNumber(
                '[data-lenticular-control="phase"]',
                0
            )
        );

        const widthMm = Math.max(
            10,
            this.getNumber(
                '[data-lenticular-control="widthMm"]',
                210
            )
        );

        const heightMm = Math.max(
            10,
            this.getNumber(
                '[data-lenticular-control="heightMm"]',
                297
            )
        );

        const views = Math.max(
            2,
            Math.min(
                12,
                this.images.length || 2
            )
        );

        const pitch = dpi / lpi;
        const strip = pitch / views;
        const naturalWidth = this.mmToPx(
            widthMm,
            dpi
        );
        const naturalHeight = this.mmToPx(
            heightMm,
            dpi
        );

        return {
            lpi,
            dpi,
            phase,
            orientation: this.root.querySelector('[data-lenticular-control="orientation"]')?.value === 'horizontal'
                ? 'horizontal' : 'vertical',
            widthMm,
            heightMm,
            views,
            pitch,
            strip,
            naturalWidth,
            naturalHeight,
        };
    }

    updateAllCalculations() {
        this.updateInterlacerMetrics();
        this.updateCalculator();
        this.updateWizard();
    }

    updateInterlacerMetrics() {
        const settings =
            this.getInterlaceSettings();

        this.setMetric(
            'pitch',
            `${this.pretty(settings.pitch)} px`
        );

        this.setMetric(
            'strip',
            `${this.pretty(settings.strip)} px`
        );

        this.setMetric(
            'output',
            `${settings.naturalWidth} × ${settings.naturalHeight} px`
        );

        const warning = this.root.querySelector(
            '[data-interlacer-warning]'
        );

        if (warning) {
            warning.textContent =
                settings.strip < 1
                    ? (
                        this.root.dataset
                            .warningLowPitch || ''
                    )
                    : '';
        }
    }

    setMetric(name, value) {
        const element = this.root.querySelector(
            `[data-metric="${name}"]`
        );

        if (element) {
            element.textContent = value;
        }
    }

    drawImageCoverSlice(
        ctx,
        image,
        targetWidth,
        targetHeight,
        position,
        thickness,
        orientation = 'vertical'
    ) {
        const horizontal = orientation === 'horizontal';
        const destX = horizontal ? 0 : position;
        const destY = horizontal ? position : 0;
        const destWidth = horizontal ? targetWidth : thickness;
        const destHeight = horizontal ? thickness : targetHeight;
        const sourceWidth = image.width;
        const sourceHeight = image.height;
        const sourceRatio = sourceWidth / sourceHeight;
        const targetRatio = targetWidth / targetHeight;

        let drawWidth;
        let drawHeight;
        let dx;
        let dy;

        if (sourceRatio > targetRatio) {
            drawHeight = targetHeight;
            drawWidth = targetHeight * sourceRatio;
            dx = (targetWidth - drawWidth) / 2;
            dy = 0;
        } else {
            drawWidth = targetWidth;
            drawHeight = targetWidth / sourceRatio;
            dx = 0;
            dy = (targetHeight - drawHeight) / 2;
        }

        const sx = (destX - dx) * (sourceWidth / drawWidth);
        const sy = (destY - dy) * (sourceHeight / drawHeight);
        const sw = destWidth * (sourceWidth / drawWidth);
        const sh = destHeight * (sourceHeight / drawHeight);

        ctx.drawImage(
            image,
            sx,
            sy,
            sw,
            sh,
            destX,
            destY,
            destWidth,
            destHeight
        );
    }

    async buildInterlacedCanvas(fullResolution) {
        if (this.images.length < 2) {
            return null;
        }

        await new Promise((resolve) => {
            requestAnimationFrame(resolve);
        });

        const settings =
            this.getInterlaceSettings();

        const maxPreviewDimension = 2200;

        const previewScale = Math.min(
            1,
            maxPreviewDimension
                / Math.max(
                    settings.naturalWidth,
                    settings.naturalHeight
                )
        );

        const scale = fullResolution
            ? 1
            : previewScale;

        const width = Math.max(
            1,
            Math.round(
                settings.naturalWidth * scale
            )
        );

        const height = Math.max(
            1,
            Math.round(
                settings.naturalHeight * scale
            )
        );

        const effectivePitch =
            settings.pitch * scale;

        const effectivePhase =
            settings.phase * scale;

        const canvas =
            document.createElement('canvas');

        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';

        const viewForPosition = (x) => {
            const wrapped = (
                (
                    (
                        x + effectivePhase
                    ) % effectivePitch
                    + effectivePitch
                )
                % effectivePitch
            );

            return Math.min(
                this.images.length - 1,
                Math.floor(
                    (
                        wrapped
                        * this.images.length
                    )
                    / effectivePitch
                )
            );
        };

        let x = 0;
        const axisLength = settings.orientation === 'horizontal' ? height : width;

        while (x < axisLength) {
            const viewIndex =
                viewForPosition(x);

            let end = x + 1;

            while (
                end < axisLength
                && viewForPosition(end)
                    === viewIndex
            ) {
                end += 1;
            }

            const image =
                this.images[viewIndex]?.bitmap
                || this.images[0]?.bitmap;

            this.drawImageCoverSlice(
                ctx,
                image,
                width,
                height,
                x,
                end - x,
                settings.orientation
            );

            x = end;
        }

        return {
            canvas,
            settings,
            previewWidth: width,
            previewHeight: height,
        };
    }

    async renderInterlacedPreview() {
        if (this.images.length < 2) {
            this.setStatus(
                this.root.dataset.waiting
                || 'Add at least 2 views'
            );
            return;
        }

        this.setStatus(
            this.root.dataset.processing
            || 'Processing…'
        );

        const result =
            await this.buildInterlacedCanvas(false);

        if (!result) {
            return;
        }

        this.interlaceCanvas.width =
            result.canvas.width;
        this.interlaceCanvas.height =
            result.canvas.height;

        const ctx =
            this.interlaceCanvas.getContext('2d');

        ctx.drawImage(result.canvas, 0, 0);

        this.root.querySelector(
            '[data-interlacer-empty]'
        )?.classList.add('is-hidden');

        const size = this.root.querySelector(
            '[data-interlacer-size]'
        );

        if (size) {
            size.textContent =
                `${result.previewWidth} × ${result.previewHeight} px `
                + `(preview)`;
        }

        this.setStatus(
            this.root.dataset.ready
            || 'Ready'
        );
    }

    async exportInterlacedPng() {
        if (this.images.length < 2) {
            this.setStatus(
                this.root.dataset.waiting
                || 'Add at least 2 views'
            );
            return;
        }

        this.setStatus(
            this.root.dataset.processing
            || 'Processing…'
        );

        const result =
            await this.buildInterlacedCanvas(true);

        downloadCanvasAsPng({
            canvas: result.canvas,
            filename:
                `lenticular-interlaced-`
                + `${new Date().toISOString().slice(0, 10)}.png`,
        });

        this.setStatus(
            this.root.dataset.ready
            || 'Ready'
        );
    }

    async exportInterlacedPdf() {
        if (this.images.length < 2) {
            this.setStatus(
                this.root.dataset.waiting
                || 'Add at least 2 views'
            );
            return;
        }

        this.setStatus(
            this.root.dataset.processing
            || 'Processing…'
        );

        const result =
            await this.buildInterlacedCanvas(true);

        downloadCanvasAsPdf({
            canvas: result.canvas,
            pageWidthMm: result.settings.widthMm,
            pageHeightMm: result.settings.heightMm,
            filename:
                `lenticular-print-ready-`
                + `${new Date().toISOString().slice(0, 10)}.pdf`,
            title: 'Lenticular Print Ready',
            subject:
                `LPI ${this.pretty(result.settings.lpi)}, `
                + `DPI ${result.settings.dpi}, `
                + `${this.images.length} views`,
            keywords:
                `lenticular, ${result.settings.widthMm}x`
                + `${result.settings.heightMm}mm, `
                + `${result.settings.dpi}dpi`,
        });

        this.setStatus(
            this.root.dataset.ready
            || 'Ready'
        );
    }

    updateCalculator() {
        const lpi = Math.max(
            20,
            this.getNumber(
                '[data-calc-control="lpi"]',
                60
            )
        );

        const dpi = Math.max(
            72,
            this.getNumber(
                '[data-calc-control="dpi"]',
                600
            )
        );

        const views = Math.max(
            2,
            Math.min(
                12,
                this.getNumber(
                    '[data-calc-control="views"]',
                    8
                )
            )
        );

        const widthMm = Math.max(
            10,
            this.getNumber(
                '[data-calc-control="width"]',
                210
            )
        );

        const heightMm = Math.max(
            10,
            this.getNumber(
                '[data-calc-control="height"]',
                297
            )
        );

        const pitch = dpi / lpi;
        const strip = pitch / views;

        const lensCount =
            (widthMm / 25.4) * lpi;

        const rasterWidth = Math.round(
            (widthMm / 25.4) * dpi
        );

        const rasterHeight = Math.round(
            (heightMm / 25.4) * dpi
        );

        this.setResult(
            'pitch',
            `${this.pretty(pitch)} px`
        );

        this.setResult(
            'strip',
            `${this.pretty(strip)} px`
        );

        this.setResult(
            'lens-count',
            this.pretty(lensCount)
        );

        this.setResult(
            'width',
            `${rasterWidth} px`
        );

        this.setResult(
            'height',
            `${rasterHeight} px`
        );

        let quality;

        if (strip < 1) {
            quality =
                this.root.dataset.qualityLow
                || 'Too few pixels per view';
        } else if (
            rasterWidth * rasterHeight
            > 40000000
        ) {
            quality =
                this.root.dataset.qualityHigh
                || 'High resolution / heavy raster';
        } else {
            quality =
                this.root.dataset.qualityGood
                || 'Good configuration';
        }

        this.setResult(
            'quality',
            quality
        );
    }

    setResult(name, value) {
        const element = this.root.querySelector(
            `[data-calc-result="${name}"]`
        );

        if (element) {
            element.textContent = value;
        }
    }

    updateWizard() {
        const size =
            this.root.querySelector(
                '[data-wizard-control="size"]'
            )?.value || 'portrait';

        const dpi = Math.max(
            72,
            this.getNumber(
                '[data-wizard-control="dpi"]',
                600
            )
        );

        const views = Math.max(
            2,
            Math.min(
                12,
                this.getNumber(
                    '[data-wizard-control="views"]',
                    8
                )
            )
        );

        const widthMm =
            size === 'landscape'
                ? 297
                : 210;

        const heightMm =
            size === 'landscape'
                ? 210
                : 297;

        const lpi = 60;
        const pitch = dpi / lpi;
        const strip = pitch / views;

        const width = Math.round(
            (widthMm / 25.4) * dpi
        );

        const height = Math.round(
            (heightMm / 25.4) * dpi
        );

        this.setWizardResult(
            'dimensions',
            `${width} × ${height} px`
        );

        this.setWizardResult(
            'physical',
            `${widthMm} × ${heightMm} mm`
        );

        this.setWizardResult(
            'pitch',
            `${this.pretty(pitch)} px / lens`
        );

        this.setWizardResult(
            'strip',
            `${this.pretty(strip)} px / view`
        );
    }

    setWizardResult(name, value) {
        const element = this.root.querySelector(
            `[data-wizard-result="${name}"]`
        );

        if (element) {
            element.textContent = value;
        }
    }

    applyWizardToInterlacer() {
        const size =
            this.root.querySelector(
                '[data-wizard-control="size"]'
            )?.value || 'portrait';

        const dpi = this.getNumber(
            '[data-wizard-control="dpi"]',
            600
        );

        const widthMm =
            size === 'landscape'
                ? 297
                : 210;

        const heightMm =
            size === 'landscape'
                ? 210
                : 297;

        const lpi = this.root.querySelector(
            '[data-lenticular-control="lpi"]'
        );

        const interlaceDpi =
            this.root.querySelector(
                '[data-lenticular-control="dpi"]'
            );

        const interlaceWidth =
            this.root.querySelector(
                '[data-lenticular-control="widthMm"]'
            );

        const interlaceHeight =
            this.root.querySelector(
                '[data-lenticular-control="heightMm"]'
            );

        if (lpi) {
            lpi.value = '60';
        }

        if (interlaceDpi) {
            interlaceDpi.value =
                String(dpi);
        }

        if (interlaceWidth) {
            interlaceWidth.value =
                String(widthMm);
        }

        if (interlaceHeight) {
            interlaceHeight.value =
                String(heightMm);
        }

        this.root.querySelector(
            '#interlacer'
        )?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });

        this.updateAllCalculations();

        if (this.images.length >= 2) {
            this.renderInterlacedPreview();
        }
    }

    getPitchTestSettings() {
        const widthMm = Math.max(
            50,
            this.getNumber(
                '[data-pitch-control="width"]',
                210
            )
        );

        const heightMm = Math.max(
            50,
            this.getNumber(
                '[data-pitch-control="height"]',
                297
            )
        );

        const dpi = Math.max(
            72,
            Math.min(
                600,
                this.getNumber(
                    '[data-pitch-control="dpi"]',
                    300
                )
            )
        );

        const start = Math.max(
            20,
            this.getNumber(
                '[data-pitch-control="start"]',
                56
            )
        );

        const end = Math.max(
            start,
            this.getNumber(
                '[data-pitch-control="end"]',
                64
            )
        );

        const step = Math.max(
            0.1,
            this.getNumber(
                '[data-pitch-control="step"]',
                1
            )
        );

        return {
            widthMm,
            heightMm,
            dpi,
            start,
            end,
            step,
            orientation: this.root.querySelector('[data-pitch-control="orientation"]')?.value === 'horizontal'
                ? 'horizontal' : 'vertical',
        };
    }

    createPitchTestCanvas(fullResolution) {
        const settings =
            this.getPitchTestSettings();

        const naturalWidth = Math.round(
            (
                settings.widthMm / 25.4
            ) * settings.dpi
        );

        const naturalHeight = Math.round(
            (
                settings.heightMm / 25.4
            ) * settings.dpi
        );

        const previewScale = Math.min(
            1,
            1800
                / Math.max(
                    naturalWidth,
                    naturalHeight
                )
        );

        const scale = fullResolution
            ? 1
            : previewScale;

        const width = Math.max(
            1,
            Math.round(
                naturalWidth * scale
            )
        );

        const height = Math.max(
            1,
            Math.round(
                naturalHeight * scale
            )
        );

        const canvas =
            document.createElement('canvas');

        canvas.width = width;
        canvas.height = height;

        const ctx =
            canvas.getContext('2d');

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(
            0,
            0,
            width,
            height
        );

        const values = [];

        for (
            let lpi = settings.start;
            lpi
                <= settings.end
                    + settings.step / 10;
            lpi += settings.step
        ) {
            values.push(
                Math.round(lpi * 100) / 100
            );

            if (values.length >= 30) {
                break;
            }
        }

        const margin =
            Math.round(width * 0.045);

        const titleHeight =
            Math.round(height * 0.08);

        const footerHeight = Math.max(
            48,
            Math.round(height * 0.1)
        );

        const available =
            height
            - titleHeight
            - margin * 2
            - footerHeight;

        const horizontal = settings.orientation === 'horizontal';
        const contentWidth = width - margin * 2;
        const bandSlot =
            (horizontal ? contentWidth : available)
            / Math.max(
                1,
                values.length
            );

        const bandHeight =
            Math.max(
                1,
                Math.floor(
                    bandSlot * 0.75
                )
            );

        ctx.fillStyle = '#17233a';

        ctx.font = (
            `700 ${Math.max(
                16,
                Math.round(
                    width * 0.018
                )
            )}px Arial, sans-serif`
        );

        ctx.fillText(
            `LENTICULAR PITCH TEST — `
                + `${settings.dpi} DPI`,
            margin,
            margin + 24
        );

        values.forEach(
            (lpi, index) => {
                ctx.save();
                if (horizontal) {
                    ctx.translate(margin + index * bandSlot, titleHeight + margin + available);
                    ctx.rotate(-Math.PI / 2);
                } else {
                    ctx.translate(margin, titleHeight + margin + index * bandSlot);
                }
                const y = 0;

                const pitch =
                    (
                        settings.dpi / lpi
                    ) * scale;

                const halfPitch =
                    Math.max(
                        0.3,
                        pitch / 2
                    );

                const bandWidth =
                    horizontal ? available : contentWidth;

                ctx.save();

                ctx.beginPath();
                ctx.rect(
                    0,
                    y,
                    bandWidth,
                    bandHeight
                );
                ctx.clip();

                let x = 0;
                let black = true;

                while (
                    x
                    < bandWidth
                ) {
                    const next = Math.min(
                        bandWidth,
                        x + halfPitch
                    );

                    ctx.fillStyle =
                        black
                            ? '#111111'
                            : '#ffffff';

                    ctx.fillRect(
                        x,
                        y,
                        next - x,
                        bandHeight
                    );

                    black = !black;
                    x = next;
                }

                ctx.restore();

                // Minimum 12 pt in the physical print.
                // 1 pt = 1/72 inch, therefore:
                // 12 pt at target DPI = DPI / 6 source pixels.
                const labelFontPx = Math.max(
                    12,
                    Math.round(
                        (settings.dpi / 6) * scale
                    )
                );

                const labelText =
                    `${this.pretty(lpi)} LPI`;

                ctx.font =
                    `700 ${labelFontPx}px Arial, sans-serif`;

                const textMetrics =
                    ctx.measureText(labelText);

                const horizontalPadding =
                    Math.max(
                        8,
                        Math.round(
                            labelFontPx * 0.55
                        )
                    );

                const verticalPadding =
                    Math.max(
                        5,
                        Math.round(
                            labelFontPx * 0.32
                        )
                    );

                const labelWidth =
                    Math.ceil(
                        textMetrics.width
                        + horizontalPadding * 2
                    );

                const labelHeight =
                    Math.ceil(
                        labelFontPx
                        + verticalPadding * 2
                    );

                const labelX =
                    8;

                const labelY =
                    y
                    + Math.max(
                        4,
                        Math.round(
                            (
                                bandHeight
                                - labelHeight
                            ) / 2
                        )
                    );

                ctx.fillStyle =
                    '#ffffff';

                ctx.fillRect(
                    labelX,
                    labelY,
                    labelWidth,
                    labelHeight
                );

                ctx.strokeStyle =
                    '#cfd6df';

                ctx.lineWidth =
                    Math.max(
                        1,
                        scale
                    );

                ctx.strokeRect(
                    labelX,
                    labelY,
                    labelWidth,
                    labelHeight
                );

                ctx.fillStyle =
                    '#17233a';

                ctx.textBaseline =
                    'middle';

                ctx.fillText(
                    labelText,
                    labelX
                        + horizontalPadding,
                    labelY
                        + labelHeight / 2
                );

                ctx.textBaseline =
                    'alphabetic';
                ctx.restore();
            }
        );

        const footerTop =
            height - footerHeight + 8;

        const barWidthPx = Math.round(
            (100 / 25.4) * settings.dpi * scale
        );

        const barHeightPx = Math.max(
            8,
            Math.round(6 * scale)
        );

        const barX = margin;
        const barY = footerTop + 18;

        ctx.fillStyle = '#17233a';
        ctx.font = (
            `700 ${Math.max(
                10,
                Math.round(
                    width * 0.011
                )
            )}px Arial, sans-serif`
        );

        ctx.fillText(
            'Calibration bar: 100 mm — print at 100% / Actual size',
            margin,
            footerTop + 8
        );

        ctx.fillStyle = '#000000';
        ctx.fillRect(
            barX,
            barY,
            barWidthPx,
            barHeightPx
        );

        ctx.strokeStyle = '#17233a';
        ctx.lineWidth = 1;
        ctx.strokeRect(
            barX,
            barY,
            barWidthPx,
            barHeightPx
        );

        ctx.fillStyle = '#17233a';
        ctx.fillText(
            '0',
            barX,
            barY + barHeightPx + 14
        );

        ctx.fillText(
            '100 mm',
            barX + barWidthPx - 36,
            barY + barHeightPx + 14
        );

        return {
            canvas,
            settings,
            naturalWidth,
            naturalHeight,
        };
    }

    generatePitchTestPreview() {
        const result =
            this.createPitchTestCanvas(false);

        this.pitchCanvas.width =
            result.canvas.width;

        this.pitchCanvas.height =
            result.canvas.height;

        const ctx =
            this.pitchCanvas.getContext('2d');

        ctx.drawImage(
            result.canvas,
            0,
            0
        );

        const size = this.root.querySelector(
            '[data-pitch-size]'
        );

        if (size) {
            size.textContent = (
                `${result.naturalWidth}`
                + ` × `
                + `${result.naturalHeight}`
                + ` px @ `
                + `${result.settings.dpi} DPI`
            );
        }
    }

    exportPitchPng() {
        const result =
            this.createPitchTestCanvas(true);

        downloadCanvasAsPng({
            canvas: result.canvas,
            filename:
                `lenticular-pitch-test-`
                + `${new Date().toISOString().slice(0, 10)}.png`,
        });
    }

    exportPitchPdf() {
        const result =
            this.createPitchTestCanvas(true);

        downloadCanvasAsPdf({
            canvas: result.canvas,
            pageWidthMm: result.settings.widthMm,
            pageHeightMm: result.settings.heightMm,
            filename:
                `lenticular-pitch-test-`
                + `${new Date().toISOString().slice(0, 10)}.pdf`,
            title: 'Lenticular Pitch Test',
            subject:
                `Pitch test ${result.settings.start}–`
                + `${result.settings.end} LPI `
                + `@ ${result.settings.dpi} DPI`,
            keywords:
                `pitch test, lenticular, ${result.settings.widthMm}x`
                + `${result.settings.heightMm}mm`,
        });
    }

    setStatus(text) {
        const element =
            this.root.querySelector(
                '[data-interlacer-status]'
            );

        if (element) {
            element.textContent = text;
        }
    }
}

const initializeLenticularLab = () => {
    document.querySelectorAll(
        '[data-lenticular-lab]'
    ).forEach((root) => {
        new LenticularLab(root);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeLenticularLab
    );
} else {
    initializeLenticularLab();
}
