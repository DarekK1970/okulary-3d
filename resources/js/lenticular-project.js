const setup = document.querySelector('[data-project-setup]');

if (setup) {
    const dpi = setup.querySelector('[name="printer_dpi"]');
    const service = setup.querySelector('[name="print_service"]');
    const output = setup.querySelector('[data-max-frames]');
    const calculate = () => {
        dpi.disabled = service.checked;
        const effectiveDpi = service.checked ? 1440 : Number(dpi.value);
        const lpi = Number(setup.querySelector('[name="lpi"]:checked')?.value || 60);
        output.textContent = String(Math.floor(effectiveDpi / lpi));
    };
    setup.addEventListener('input', calculate);
    calculate();
}

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
