export const initEditorImages = (state) => {
    const { editor, wrapper } = state;
    let savedRange = null;
    let selected = null;
    let drag = null;
    const frame = document.createElement('div');
    frame.className = 'editor-image-frame';
    frame.hidden = true;
    frame.setAttribute('role', 'group');
    frame.setAttribute('aria-label', 'Zmień rozmiar obrazu');
    wrapper.append(frame);

    const remember = () => {
        const selection = window.getSelection();
        if (selection?.rangeCount && editor.contains(selection.getRangeAt(0).commonAncestorContainer)) {
            savedRange = selection.getRangeAt(0).cloneRange();
        }
    };

    const restore = () => {
        editor.focus();
        const selection = window.getSelection();
        const range = savedRange && editor.contains(savedRange.commonAncestorContainer)
            ? savedRange.cloneRange() : document.createRange();
        if (!savedRange || !editor.contains(savedRange.commonAncestorContainer)) {
            range.selectNodeContents(editor);
            range.collapse(false);
        }
        selection.removeAllRanges();
        selection.addRange(range);
    };

    const position = () => {
        if (!selected || !editor.contains(selected) || state.sourceMode || !editor.getClientRects().length) {
            frame.hidden = true;
            return;
        }
        const imageRect = selected.getBoundingClientRect();
        const editorRect = editor.getBoundingClientRect();
        const wrapperRect = wrapper.getBoundingClientRect();
        frame.hidden = imageRect.bottom <= editorRect.top || imageRect.top >= editorRect.bottom;
        Object.assign(frame.style, {
            left: `${imageRect.left - wrapperRect.left - wrapper.clientLeft}px`,
            top: `${imageRect.top - wrapperRect.top - wrapper.clientTop}px`,
            width: `${imageRect.width}px`,
            height: `${imageRect.height}px`,
            clipPath: `inset(${imageRect.top < editorRect.top ? editorRect.top - imageRect.top : -10}px -10px ${imageRect.bottom > editorRect.bottom ? imageRect.bottom - editorRect.bottom : -10}px -10px)`,
        });
    };

    const select = (image) => {
        selected = image;
        position();
    };

    // Replacing through the editing command keeps image changes in native undo history.
    const replace = (image, html) => {
        editor.focus();
        const range = document.createRange();
        range.selectNode(image);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        const previousImages = new Set(editor.querySelectorAll('img'));
        document.execCommand('insertHTML', false, html);
        select([...editor.querySelectorAll('img')].find((item) => !previousImages.has(item)) || null);
        remember();
        state.sync();
    };

    state.insertImage = (url, alt = '') => {
        if (state.readonly || state.sourceMode || !/^(https?:\/\/|\/|\.\/|\.\.\/)/i.test(url)) return;
        restore();
        const image = document.createElement('img');
        image.src = url;
        image.alt = alt;
        image.style.display = 'block';
        image.style.marginLeft = 'auto';
        image.style.marginRight = 'auto';
        const previousImages = new Set(editor.querySelectorAll('img'));
        document.execCommand('insertHTML', false, image.outerHTML);
        select([...editor.querySelectorAll('img')].find((item) => !previousImages.has(item)) || null);
        remember();
        state.sync();
    };
    state.rememberSelection = remember;
    state.restoreSelection = restore;
    state.clearImageSelection = () => select(null);
    state.alignImage = (command) => {
        if (!selected || !editor.contains(selected) || state.readonly || state.sourceMode) return false;
        const alignment = { justifyLeft: ['0', 'auto'], justifyCenter: ['auto', 'auto'], justifyRight: ['auto', '0'] }[command];
        if (!alignment) return false;
        const image = selected.cloneNode(true);
        image.style.display = 'block';
        [image.style.marginLeft, image.style.marginRight] = alignment;
        replace(selected, image.outerHTML);
        return true;
    };

    const resize = (width) => {
        const styles = getComputedStyle(selected.parentElement);
        const available = selected.parentElement.clientWidth - parseFloat(styles.paddingLeft) - parseFloat(styles.paddingRight);
        selected.setAttribute('width', String(Math.round(Math.max(24, Math.min(width, available, 9999)))));
        selected.removeAttribute('height');
        selected.style.removeProperty('width');
        selected.style.removeProperty('height');
        position();
    };

    for (const corner of ['nw', 'ne', 'sw', 'se']) {
        const handle = document.createElement('button');
        handle.type = 'button';
        handle.className = `editor-image-handle editor-image-handle-${corner}`;
        handle.setAttribute('aria-label', 'Rozmiar obrazu — przeciągnij lub użyj strzałek');
        handle.title = 'Przeciągnij, aby skalować proporcjonalnie; strzałki zmieniają szerokość';
        frame.append(handle);
        handle.addEventListener('pointerdown', (event) => {
            if (event.button !== 0 || !selected || state.readonly) return;
            event.preventDefault();
            const rect = selected.getBoundingClientRect();
            drag = { x: event.clientX, y: event.clientY, width: rect.width, ratio: rect.width / rect.height, html: selected.outerHTML };
            handle.setPointerCapture(event.pointerId);
        });
        handle.addEventListener('pointermove', (event) => {
            if (!drag) return;
            const dx = (event.clientX - drag.x) * (corner.includes('w') ? -1 : 1);
            const dy = (event.clientY - drag.y) * (corner.includes('n') ? -1 : 1) * drag.ratio;
            resize(drag.width + (Math.abs(dx) >= Math.abs(dy) ? dx : dy));
        });
        const finish = (cancelled = false) => {
            if (!drag) return;
            const html = cancelled ? drag.html : selected.outerHTML;
            const original = document.createElement('template');
            original.innerHTML = drag.html;
            const image = original.content.firstChild;
            selected.replaceWith(image);
            selected = image;
            drag = null;
            replace(selected, html);
        };
        handle.addEventListener('pointerup', () => finish());
        handle.addEventListener('pointercancel', () => finish(true));
        handle.addEventListener('lostpointercapture', () => finish());
        handle.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                select(null);
                restore();
                return;
            }
            if (!selected || !['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
            event.preventDefault();
            const original = selected.outerHTML;
            resize(selected.getBoundingClientRect().width + (['ArrowRight', 'ArrowUp'].includes(event.key) ? 10 : -10));
            const html = selected.outerHTML;
            const template = document.createElement('template');
            template.innerHTML = original;
            const image = template.content.firstChild;
            selected.replaceWith(image);
            replace(image, html);
            handle.focus();
        });
    }

    document.addEventListener('selectionchange', remember);
    editor.addEventListener('click', (event) => {
        if (state.readonly) return;
        select(event.target.closest('img'));
    });
    editor.addEventListener('dragstart', (event) => {
        if (event.target.tagName === 'IMG') event.preventDefault();
    });
    document.addEventListener('pointerdown', (event) => {
        if (!wrapper.contains(event.target)) select(null);
    });
    editor.addEventListener('keydown', () => select(null));
    editor.addEventListener('input', position);
    wrapper.addEventListener('click', position);
    editor.addEventListener('scroll', position);
    editor.addEventListener('load', position, true);
    new ResizeObserver(position).observe(wrapper);
    new ResizeObserver(position).observe(editor);
};
