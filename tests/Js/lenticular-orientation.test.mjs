import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const source = readFileSync(new URL('../../resources/js/lenticular-lab.js', import.meta.url), 'utf8')
    .replace(/^import\s*\{[^}]+\}\s*from\s*'\.\/pdf-export';/, '');

function fixture(settings) {
    const draws = [];
    const Lab = vm.runInNewContext(`${source}\nLenticularLab`, {
        requestAnimationFrame: (callback) => callback(),
        document: {
            readyState: 'loading',
            addEventListener() {},
            createElement: () => ({ getContext: () => ({ drawImage: (...args) => draws.push(args) }) }),
        },
    });
    const lab = Object.create(Lab.prototype);
    lab.images = [0, 1].map((id) => ({ bitmap: { id, width: 8, height: 8 } }));
    lab.getInterlaceSettings = () => ({ naturalWidth: 8, naturalHeight: 6, pitch: 4, phase: 0, ...settings });
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
