import test from 'node:test';
import assert from 'node:assert/strict';
import { createPdfBlob } from '../../resources/js/pdf-export.js';

for (const [widthMm, heightMm] of [[210, 297], [297, 210], [100, 150]]) {
    for (const dpi of [300, 600]) {
        test(`PDF preserves 60 LPI on both axes: ${widthMm}x${heightMm}mm at ${dpi} DPI`, async () => {
            // Raster encoding is irrelevant to the PDF placement matrix tested here.
            const canvas = {
                width: Math.round(widthMm * dpi / 25.4),
                height: Math.round(heightMm * dpi / 25.4),
                toDataURL: () => 'data:image/jpeg;base64,/9j/2Q==',
            };
            const pdf = await createPdfBlob({
                canvas, pageWidthMm: widthMm, pageHeightMm: heightMm,
                title: 'Pitch test', subject: 'Scale regression', keywords: 'lenticular',
            }).text();
            const page = pdf.match(/\/MediaBox \[0 0 ([\d.]+) ([\d.]+)\]/);
            const image = pdf.match(/([\d.]+) 0 0 ([\d.]+) 0 0 cm/);
            assert.ok(page && image);
            assert.equal(Number(image[1]), Number(page[1]));
            assert.equal(Number(image[2]), Number(page[2]));
            const pitchPixels = dpi / 60;
            const printedLpiX = canvas.width / (Number(image[1]) / 72) / pitchPixels;
            const printedLpiY = canvas.height / (Number(image[2]) / 72) / pitchPixels;
            assert.ok(Math.abs(printedLpiX - 60) < 0.02, `X pitch is ${printedLpiX}`);
            assert.ok(Math.abs(printedLpiY - 60) < 0.02, `Y pitch is ${printedLpiY}`);
        });
    }
}
