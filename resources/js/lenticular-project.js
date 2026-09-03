const stage = document.querySelector('[data-alignment-stage]');

if (stage) {
    const controls = {
        center: document.querySelector('#z-center'),
        width: document.querySelector('#z-width'),
        alignmentY: document.querySelector('#alignment-y'),
    };
    const zone = stage.querySelector('[data-z-zone]');

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
    updateGuide();
}
