const stage = document.querySelector('[data-alignment-stage]');

if (stage) {
    const controls = {
        center: document.querySelector('#z-center'),
        width: document.querySelector('#z-width'),
        alignmentY: document.querySelector('#alignment-y'),
    };

    const updateGuide = () => {
        stage.style.setProperty('--z-center', `${Number(controls.center.value) * 100}%`);
        stage.style.setProperty('--z-width', `${Number(controls.width.value) * 100}%`);
        stage.style.setProperty('--alignment-y', `${Number(controls.alignmentY.value) * 100}%`);
        Object.values(controls).forEach((control) => {
            const output = document.querySelector(`[data-range-output="${control.id}"]`);
            if (output) output.textContent = `${Math.round(Number(control.value) * 100)}%`;
        });
    };

    Object.values(controls).forEach((control) => control?.addEventListener('input', updateGuide));
    document.querySelectorAll('[data-overlay-toggle]').forEach((checkbox) => {
        const frame = stage.querySelector(`.frame-${checkbox.dataset.overlayToggle}`);
        checkbox.addEventListener('change', () => frame?.classList.toggle('is-visible', checkbox.checked));
    });
    updateGuide();
}
