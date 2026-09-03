import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const source = readFileSync(new URL('../../resources/js/lenticular-lab.js', import.meta.url), 'utf8')
    .replace(/^import\s*\{[^}]+\}\s*from\s*'\.\/pdf-export';/, '');

function fixture(settings, context = null) {
    const draws = [];
    const Lab = vm.runInNewContext(`${source}\nLenticularLab`, {
        requestAnimationFrame: (callback) => callback(),
        document: {
            readyState: 'loading',
            addEventListener() {},
            createElement: () => ({ getContext: () => context || ({ drawImage: (...args) => draws.push(args) }) }),
        },
    });
    const lab = Object.create(Lab.prototype);
    lab.images = [0, 1].map((id) => ({ bitmap: { id, width: 8, height: 8 } }));
    lab.getInterlaceSettings = () => ({ naturalWidth: 8, naturalHeight: 6, widthMm: 8, heightMm: 6, pitch: 4, phase: 0, ...settings });
    return { lab, draws };
}

for (const orientation of ['vertical', 'horizontal']) {
    test(`${orientation}: phase and view order produce complete strips without rotating the source`, async () => {
        const { lab, draws } = fixture({ orientation, phase: 1 });
        const { canvas } = await lab.buildInterlacedCanvas(true);
        assert.equal(canvas.width, 8);
        assert.equal(canvas.height, 6);
        const horizontal = orientation === 'horizontal';
        const axis = horizontal ? 6 : 8;
        const views = Array(axis).fill(null);
        for (const [image, sx, sy, sw, sh, x, y, width, height] of draws) {
            assert.equal(horizontal ? x : y, 0);
            assert.equal(horizontal ? width : height, horizontal ? 8 : 6);
            // Square source is cropped by one pixel at the top, never rotated.
            assert.equal(sx, x);
            assert.equal(sy, y + 1);
            assert.equal(sw, width);
            assert.equal(sh, height);
            const start = horizontal ? y : x;
            const length = horizontal ? height : width;
            for (let i = start; i < start + length; i++) {
                assert.equal(views[i], null);
                views[i] = image.id;
            }
        }
        assert.deepEqual(views, horizontal ? [0, 1, 1, 0, 0, 1] : [0, 1, 1, 0, 0, 1, 1, 0]);
    });
}

test('horizontal preview scales pitch and phase with output dimensions', async () => {
    const { lab, draws } = fixture({ orientation: 'horizontal', naturalWidth: 4400, naturalHeight: 12, pitch: 8, phase: 2 });
    const { canvas } = await lab.buildInterlacedCanvas(false);
    assert.equal(canvas.width, 2200);
    assert.equal(canvas.height, 6);
    assert.deepEqual(draws.map(([image, , , , , , y, , height]) => [image.id, y, height]),
        [[0, 0, 1], [1, 1, 2], [0, 3, 2], [1, 5, 1]]);
});

test('orientation change recalculates and renders only when source views are loaded', () => {
    const { lab } = fixture();
    const events = {};
    let calculations = 0;
    let renders = 0;
    lab.root = { querySelectorAll: () => [{ dataset: { lenticularControl: 'orientation' }, addEventListener: (event, handler) => { events[event] = handler; } }] };
    lab.updateAllCalculations = () => { calculations++; };
    lab.renderInterlacedPreview = () => { renders++; };
    lab.bindControls();
    events.change();
    assert.equal(calculations, 1);
    assert.equal(renders, 1);
    lab.images = [];
    events.change();
    assert.equal(renders, 1);
});

function pitchFixture(orientation, step = 1) {
    const clips = [];
    const stripes = [];
    const labels = [];
    const stack = [];
    let transform = [1, 0, 0, 1, 0, 0];
    const rectangle = (x, y, w, h) => {
        const [a, b, c, d, tx, ty] = transform;
        const corners = [[x, y], [x + w, y], [x, y + h], [x + w, y + h]]
            .map(([px, py]) => [a * px + c * py + tx, b * px + d * py + ty]);
        const xs = corners.map(([px]) => px);
        const ys = corners.map(([, py]) => py);
        return { x: Math.min(...xs), y: Math.min(...ys), width: Math.max(...xs) - Math.min(...xs), height: Math.max(...ys) - Math.min(...ys) };
    };
    const context = {
        save() { stack.push([...transform]); },
        restore() { transform = stack.pop(); },
        translate(x, y) { transform[4] += x; transform[5] += y; },
        rotate(angle) { transform[0] = Math.cos(angle); transform[1] = Math.sin(angle); transform[2] = -Math.sin(angle); transform[3] = Math.cos(angle); },
        beginPath() {}, clip() {}, strokeRect() {},
        rect(...args) { clips.push(rectangle(...args)); },
        fillRect(...args) { if (this.fillStyle === '#111111') stripes.push(rectangle(...args)); },
        fillText(text) { if (text.endsWith(' LPI')) labels.push(text); },
        measureText: () => ({ width: 150 }),
    };
    const { lab } = fixture({}, context);
    lab.getPitchTestSettings = () => ({ widthMm: 210, heightMm: 297, dpi: 300, start: 56, end: 64, step, orientation });
    return { lab, clips, stripes, labels, stack };
}

for (const fullResolution of [false, true]) {
    test(`pitch test preserves LPI samples and page dimensions across orientations (${fullResolution ? 'export' : 'preview'})`, () => {
        const vertical = pitchFixture('vertical');
        const horizontal = pitchFixture('horizontal');
        const first = vertical.lab.createPitchTestCanvas(fullResolution);
        const second = horizontal.lab.createPitchTestCanvas(fullResolution);
        assert.equal(first.canvas.width, second.canvas.width);
        assert.equal(first.canvas.height, second.canvas.height);
        assert.deepEqual(horizontal.labels, ['56 LPI', '57 LPI', '58 LPI', '59 LPI', '60 LPI', '61 LPI', '62 LPI', '63 LPI', '64 LPI']);
        assert.deepEqual(vertical.labels, horizontal.labels);
        assert.equal(horizontal.clips.length, 9);
        assert.equal(vertical.clips.length, 9);
        assert.ok(horizontal.stripes[0].width > horizontal.stripes[0].height);
        assert.ok(vertical.stripes[0].height > vertical.stripes[0].width);
        assert.ok(Math.abs(horizontal.stripes[0].height - vertical.stripes[0].width) < 0.00001);
        horizontal.clips.forEach((band, i) => {
            assert.ok(band.x >= 0 && band.x + band.width <= second.canvas.width);
            assert.ok(band.y >= 0 && band.y + band.height <= second.canvas.height);
            if (i) assert.ok(band.x >= horizontal.clips[i - 1].x + horizontal.clips[i - 1].width);
        });
        assert.equal(horizontal.stack.length, 0);
    });
}

test('horizontal pitch test fits the maximum 30 bands on the page', () => {
    const { lab, clips, labels } = pitchFixture('horizontal', 0.1);
    const { canvas } = lab.createPitchTestCanvas(false);
    assert.equal(labels.length, 30);
    assert.equal(clips.length, 30);
    assert.ok(clips[29].x + clips[29].width <= canvas.width);
    assert.ok(clips[0].width < clips[1].x - clips[0].x);
});

test('pitch orientation change automatically regenerates its preview', () => {
    const { lab } = fixture();
    const events = {};
    let renders = 0;
    lab.root = { querySelectorAll: () => [{ dataset: { pitchControl: 'orientation' }, addEventListener: (event, handler) => { events[event] = handler; } }] };
    lab.updateAllCalculations = () => {};
    lab.generatePitchTestPreview = () => { renders++; };
    lab.bindControls();
    events.change();
    assert.equal(renders, 1);
});

test('small pitch test exports retain requested DPI rather than stretching to a minimum canvas size', () => {
    const { lab } = pitchFixture('horizontal');
    lab.getPitchTestSettings = () => ({ widthMm: 50, heightMm: 50, dpi: 72, start: 60, end: 60, step: 1, orientation: 'horizontal' });
    const result = lab.createPitchTestCanvas(true);
    assert.equal(result.canvas.width, 142);
    assert.equal(result.canvas.height, 142);
});

for (const orientation of ['vertical', 'horizontal']) {
    for (const alignmentLines of [4, 6, 8]) {
        test(`${alignmentLines} alignment lines: ${orientation} placement, phase and unchanged image scale`, async () => {
            const fills = [];
            const copies = [];
            const context = {
                fillRect(...rect) { fills.push({ color: this.fillStyle, rect }); },
                drawImage(...args) { copies.push(args); },
            };
            const { lab } = fixture({ orientation, alignmentLines, phase: 1 }, context);
            const result = await lab.buildInterlacedCanvas(true);
            const horizontal = orientation === 'horizontal';
            assert.equal(result.canvas.width, horizontal ? 8 : 8 + alignmentLines * 4);
            assert.equal(result.canvas.height, horizontal ? 6 + alignmentLines * 4 : 6);
            const [original, x, y] = copies.at(-1);
            assert.equal(original.width, 8);
            assert.equal(original.height, 6);
            assert.equal(x, horizontal ? 0 : alignmentLines * 4);
            assert.equal(y, 0);
            assert.equal(copies.at(-1).length, 3); // No resampling of the source image.
            assert.equal(result.pageWidthMm / result.canvas.width, 1);
            assert.equal(result.pageHeightMm / result.canvas.height, 1);
            const black = fills.filter(({ color }) => color === '#000000').map(({ rect }) => horizontal ? rect[1] : rect[0]);
            assert.equal(black.length, alignmentLines * 2);
            assert.deepEqual(black.slice(0, 4), horizontal ? [7, 8, 11, 12] : [0, 3, 4, 7]);
        });
    }
}

test('zero alignment lines keeps the original canvas and dimensions', async () => {
    const { lab } = fixture({ alignmentLines: 0 });
    const result = await lab.buildInterlacedCanvas(true);
    assert.equal(result.canvas.width, 8);
    assert.equal(result.canvas.height, 6);
    assert.equal(result.pageWidthMm, 8);
    assert.equal(result.pageHeightMm, 6);
});
