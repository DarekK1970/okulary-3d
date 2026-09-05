const timelineForm = document.querySelector('[data-frame-timeline]');

if (timelineForm) {
    const frameCount = Number(timelineForm.dataset.frameCount);
    const maximumFrames = Number(timelineForm.dataset.maxFrames);
    const step = timelineForm.querySelector('[data-frame-step]');
    const slider = timelineForm.querySelector('[data-timeline-slider]');
    const selection = timelineForm.querySelector('[data-timeline-selection]');
    const startInput = timelineForm.querySelector('[data-frame-start]');
    const endInput = timelineForm.querySelector('[data-frame-end]');
    let dragOffset = 0;

    const render = (requestedStart = Number(slider.value)) => {
        const interval = Number(step.value);
        const selectedCount = Math.min(maximumFrames, Math.floor((frameCount - 1) / interval) + 1);
        const span = Math.max(0, (selectedCount - 1) * interval);
        const start = Math.min(Math.max(0, Math.round(requestedStart)), frameCount - 1 - span);
        const end = start + span;
        slider.max = String(Math.max(0, frameCount - 1 - span));
        slider.value = String(start);
        startInput.value = String(start);
        endInput.value = String(end);
        timelineForm.querySelector('[data-range-start]').textContent = String(start);
        timelineForm.querySelector('[data-range-end]').textContent = String(end);
        timelineForm.querySelector('[data-selected-count]').textContent = String(selectedCount);
        selection.style.left = `${start / Math.max(1, frameCount - 1) * 100}%`;
        selection.style.width = `${span / Math.max(1, frameCount - 1) * 100}%`;
        timelineForm.querySelectorAll('[data-frame-index]').forEach((item) => item.classList.toggle('is-selected', Number(item.dataset.frameIndex) >= start && Number(item.dataset.frameIndex) <= end));
    };

    slider.addEventListener('input', () => render());
    step.addEventListener('change', () => render());
    selection.addEventListener('pointerdown', (event) => {
        const bounds = selection.parentElement.getBoundingClientRect();
        dragOffset = event.clientX - bounds.left - selection.offsetLeft;
        selection.setPointerCapture(event.pointerId);
    });
    selection.addEventListener('pointermove', (event) => {
        if (!selection.hasPointerCapture(event.pointerId)) return;
        const bounds = selection.parentElement.getBoundingClientRect();
        render(((event.clientX - bounds.left - dragOffset) / bounds.width) * (frameCount - 1));
    });
    render(0);
}

const stage = document.querySelector('[data-alignment-stage]');

if (stage) {
    const controls = {
        center: document.querySelector('#z-center'),
        width: document.querySelector('#z-width'),
        alignmentY: document.querySelector('#alignment-y'),
    };
    const zone = stage.querySelector('[data-z-zone]');
    const preview = stage.closest('.lenticular-alignment-preview');

    const fitStageToViewport = () => {
        const sourceWidth = Number(stage.dataset.sourceWidth) || 3;
        const sourceHeight = Number(stage.dataset.sourceHeight) || 2;
        const ratio = sourceWidth / sourceHeight;
        const widthControlHeight = preview?.querySelector('.lenticular-width-control')?.offsetHeight || 48;
        const availableHeight = Math.max(80, window.innerHeight - stage.getBoundingClientRect().top - widthControlHeight - 24);
        const fittedWidth = Math.min(window.innerWidth * 0.6, 900, availableHeight * ratio);
        stage.style.width = `${Math.max(1, fittedWidth)}px`;
        stage.style.setProperty('--source-ratio', String(ratio));
        stage.closest('.lenticular-alignment-editor')?.style.setProperty('--stage-height', `${stage.offsetHeight}px`);
    };

    const updateGuide = () => {
        const width = Number(controls.width.value);
        const halfWidth = width / 2;
        const center = Math.min(1 - halfWidth, Math.max(halfWidth, Number(controls.center.value)));
        controls.center.value = String(center);
        stage.style.setProperty('--z-left', `${(center - halfWidth) * 100}%`);
        stage.style.setProperty('--z-width', `${width * 100}%`);
        stage.style.setProperty('--alignment-y', `${Number(controls.alignmentY.value) * 100}%`);
        Object.values(controls).forEach((control) => {
            const output = document.querySelector(`[data-range-output="${control.id}"]`);
            if (output) output.textContent = `${Math.round(Number(control.value) * 100)}%`;
        });
        zone?.setAttribute('aria-valuenow', String(Math.round(center * 100)));
    };

    const moveZone = (clientX) => {
        const bounds = stage.getBoundingClientRect();
        const halfWidth = Number(controls.width.value) / 2;
        const position = (clientX - bounds.left) / bounds.width;
        controls.center.value = String(Math.min(1 - halfWidth, Math.max(halfWidth, position)));
        updateGuide();
    };

    Object.values(controls).forEach((control) => control?.addEventListener('input', updateGuide));
    document.querySelectorAll('[data-overlay-toggle]').forEach((checkbox) => {
        const frame = stage.querySelector(`.frame-${checkbox.dataset.overlayToggle}`);
        checkbox.addEventListener('change', () => frame?.classList.toggle('is-visible', checkbox.checked));
    });
    zone?.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        zone.setPointerCapture(event.pointerId);
        moveZone(event.clientX);
    });
    zone?.addEventListener('pointermove', (event) => {
        if (zone.hasPointerCapture(event.pointerId)) moveZone(event.clientX);
    });
    zone?.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        const direction = event.key === 'ArrowLeft' ? -1 : 1;
        const halfWidth = Number(controls.width.value) / 2;
        controls.center.value = String(Math.min(1 - halfWidth, Math.max(halfWidth, Number(controls.center.value) + direction * 0.01)));
        updateGuide();
    });
    window.addEventListener('resize', fitStageToViewport);
    fitStageToViewport();
    updateGuide();
}

const cropForm = document.querySelector('[data-crop-form]');

if (cropForm) {
    const stage = cropForm.querySelector('[data-crop-stage]');
    const selection = cropForm.querySelector('[data-crop-selection]');
    const ratioSelect = cropForm.querySelector('[data-crop-ratio]');
    const inputs = {
        x: cropForm.querySelector('[data-crop-x]'),
        y: cropForm.querySelector('[data-crop-y]'),
        width: cropForm.querySelector('[data-crop-width]'),
        height: cropForm.querySelector('[data-crop-height]'),
    };
    let start = null;

    const draw = (crop) => {
        selection.style.left = `${crop.x * 100}%`;
        selection.style.top = `${crop.y * 100}%`;
        selection.style.width = `${crop.width * 100}%`;
        selection.style.height = `${crop.height * 100}%`;
        Object.entries(crop).forEach(([key, value]) => { inputs[key].value = value.toFixed(6); });
    };

    const point = (event) => {
        const bounds = stage.getBoundingClientRect();
        return {
            x: Math.min(1, Math.max(0, (event.clientX - bounds.left) / bounds.width)),
            y: Math.min(1, Math.max(0, (event.clientY - bounds.top) / bounds.height)),
        };
    };

    const update = (event) => {
        if (!start) return;
        const current = point(event);
        let width = Math.abs(current.x - start.x);
        let height = Math.abs(current.y - start.y);
        const ratio = Number(ratioSelect.value);
        if (ratio) {
            const pixelRatio = ratio * (stage.clientHeight / stage.clientWidth);
            if (width / Math.max(height, 0.000001) > pixelRatio) height = width / pixelRatio;
            else width = height * pixelRatio;
        }
        const directionX = current.x >= start.x ? 1 : -1;
        const directionY = current.y >= start.y ? 1 : -1;
        width = Math.min(width, directionX > 0 ? 1 - start.x : start.x);
        height = Math.min(height, directionY > 0 ? 1 - start.y : start.y);
        draw({ x: directionX > 0 ? start.x : start.x - width, y: directionY > 0 ? start.y : start.y - height, width, height });
    };

    stage.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        start = point(event);
        stage.setPointerCapture(event.pointerId);
        draw({ x: start.x, y: start.y, width: 0.01, height: 0.01 });
    });
    stage.addEventListener('pointermove', update);
    stage.addEventListener('pointerup', (event) => {
        update(event);
        start = null;
        stage.releasePointerCapture(event.pointerId);
    });
    draw({ x: 0, y: 0, width: 1, height: 1 });
}

const animation = document.querySelector('[data-sequence-animation]');

if (animation) {
    const frames = [...animation.querySelectorAll('img')];
    const toggle = document.querySelector('[data-animation-toggle]');
    let index = 0;
    let direction = 1;
    let running = true;
    const delay = frames.length > 1 ? 700 / (frames.length - 1) : 700;
    const timer = window.setInterval(() => {
        if (!running || frames.length < 2) return;
        frames[index].classList.remove('is-visible');
        if (index === frames.length - 1) direction = -1;
        if (index === 0) direction = 1;
        index += direction;
        frames[index].classList.add('is-visible');
    }, delay);
    toggle?.addEventListener('click', () => {
        running = !running;
        toggle.textContent = toggle.dataset[running ? 'pauseLabel' : 'playLabel'];
    });
    window.addEventListener('pagehide', () => window.clearInterval(timer), { once: true });
}
