const slugify = (value) => value
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/ł/g, 'l')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-+/g, '-');

const initSlugGenerator = () => {
    const source = document.querySelector('[data-slug-source]');
    const target = document.querySelector('[data-slug-target]');

    if (!source || !target) {
        return;
    }

    let manuallyEdited = target.value.trim() !== '';

    target.addEventListener('input', () => {
        manuallyEdited = target.value.trim() !== '';
    });

    source.addEventListener('input', () => {
        if (!manuallyEdited) {
            target.value = slugify(source.value);
        }
    });
};

const ADVANCED_EDITOR_ALLOWED_TAGS = new Set([
    'P', 'BR', 'H2', 'H3', 'H4',
    'STRONG', 'B', 'EM', 'I', 'U', 'S',
    'UL', 'OL', 'LI', 'BLOCKQUOTE',
    'A', 'SPAN', 'HR', 'PRE', 'CODE',
    'SUP', 'SUB', 'TABLE', 'THEAD', 'TBODY',
    'TFOOT', 'TR', 'TH', 'TD', 'IMG',
]);

const sanitizePastedHtml = (html) => {
    const template = document.createElement('template');
    template.innerHTML = html;

    template.content.querySelectorAll(
        'script,style,iframe,object,embed,form,input,button,textarea,select,meta,link'
    ).forEach((node) => node.remove());

    const walker = document.createTreeWalker(
        template.content,
        NodeFilter.SHOW_ELEMENT,
    );

    const elements = [];
    while (walker.nextNode()) {
        elements.push(walker.currentNode);
    }

    elements.forEach((element) => {
        if (!ADVANCED_EDITOR_ALLOWED_TAGS.has(element.tagName)) {
            element.replaceWith(...element.childNodes);
            return;
        }

        [...element.attributes].forEach((attribute) => {
            const name = attribute.name.toLowerCase();

            if (
                name.startsWith('on')
                || ['class', 'id', 'contenteditable'].includes(name)
            ) {
                element.removeAttribute(attribute.name);
                return;
            }

            if (name === 'style') {
                const safe = attribute.value
                    .split(';')
                    .map((declaration) => declaration.trim())
                    .filter(Boolean)
                    .filter((declaration) => {
                        const [property, ...rest] = declaration.split(':');
                        const value = rest.join(':').trim();
                        const key = property?.trim().toLowerCase();

                        if (!key || !value) {
                            return false;
                        }

                        if (
                            /url\s*\(|expression\s*\(|behavior\s*:|-moz-binding/i.test(value)
                        ) {
                            return false;
                        }

                        return [
                            'text-align',
                            'color',
                            'background-color',
                            'font-size',
                            'font-family',
                            'text-decoration',
                        ].includes(key);
                    })
                    .join('; ');

                if (safe) {
                    element.setAttribute('style', safe);
                } else {
                    element.removeAttribute('style');
                }

                return;
            }

            const perTag = {
                A: ['href', 'target', 'rel', 'title'],
                IMG: ['src', 'alt', 'title', 'width', 'height'],
                TH: ['colspan', 'rowspan', 'scope'],
                TD: ['colspan', 'rowspan'],
            };

            if (!(perTag[element.tagName] || []).includes(name)) {
                element.removeAttribute(attribute.name);
            }
        });

        if (element.tagName === 'A') {
            const href = element.getAttribute('href') || '';

            if (/^\s*(javascript:|data:)/i.test(href)) {
                element.removeAttribute('href');
            }

            if (element.getAttribute('target') === '_blank') {
                element.setAttribute('rel', 'noopener noreferrer');
            }
        }

        if (element.tagName === 'IMG') {
            const src = element.getAttribute('src') || '';

            if (
                src
                && !/^(https?:\/\/|\/|\.\/|\.\.\/)/i.test(src)
            ) {
                element.removeAttribute('src');
            }
        }
    });

    return template.innerHTML.trim();
};

const button = ({
    label,
    title,
    command = null,
    value = null,
    action = null,
    className = '',
}) => {
    const element = document.createElement('button');
    element.type = 'button';
    element.className = `advanced-wysiwyg-button ${className}`.trim();
    element.innerHTML = label;
    element.title = title;
    element.setAttribute('aria-label', title);

    if (command) {
        element.dataset.editorCommand = command;
    }

    if (value !== null) {
        element.dataset.editorValue = value;
    }

    if (action) {
        element.dataset.editorAction = action;
    }

    return element;
};

const separator = () => {
    const element = document.createElement('span');
    element.className = 'advanced-wysiwyg-separator';
    element.setAttribute('aria-hidden', 'true');
    return element;
};

const toolbarSelect = (title, options, action) => {
    const select = document.createElement('select');
    select.className = 'advanced-wysiwyg-select';
    select.title = title;
    select.setAttribute('aria-label', title);
    select.dataset.editorSelect = action;

    options.forEach(([value, label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        select.appendChild(option);
    });

    return select;
};

const insertHtml = (html) => {
    document.execCommand('insertHTML', false, html);
};

const normaliseUrl = (url) => {
    const value = (url || '').trim();

    if (!value) {
        return '';
    }

    if (/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(value)) {
        return value;
    }

    return `https://${value}`;
};

const tableMarkup = (rows, columns, header) => {
    let html = '<table>';

    if (header) {
        html += '<thead><tr>';

        for (let column = 0; column < columns; column += 1) {
            html += '<th scope="col">Nagłówek</th>';
        }

        html += '</tr></thead>';
        rows -= 1;
    }

    html += '<tbody>';

    for (let row = 0; row < rows; row += 1) {
        html += '<tr>';

        for (let column = 0; column < columns; column += 1) {
            html += '<td>Treść</td>';
        }

        html += '</tr>';
    }

    html += '</tbody></table><p><br></p>';

    return html;
};

const createAdvancedToolbar = (wrapper, editor, output, source, state) => {
    const toolbar = document.createElement('div');
    toolbar.className = 'advanced-wysiwyg-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Zaawansowany edytor tekstu');

    toolbar.append(
        button({
            label: '↶',
            title: 'Cofnij',
            command: 'undo',
        }),
        button({
            label: '↷',
            title: 'Ponów',
            command: 'redo',
        }),
        separator(),
        toolbarSelect(
            'Format akapitu',
            [
                ['p', 'Akapit'],
                ['h2', 'Nagłówek H2'],
                ['h3', 'Nagłówek H3'],
                ['h4', 'Nagłówek H4'],
                ['blockquote', 'Cytat'],
                ['pre', 'Kod / PRE'],
            ],
            'format',
        ),
        toolbarSelect(
            'Rozmiar tekstu',
            [
                ['', 'Rozmiar'],
                ['2', 'Mały'],
                ['3', 'Normalny'],
                ['4', 'Duży'],
                ['5', 'Bardzo duży'],
            ],
            'fontSize',
        ),
        separator(),
        button({
            label: '<strong>B</strong>',
            title: 'Pogrubienie',
            command: 'bold',
        }),
        button({
            label: '<em>I</em>',
            title: 'Kursywa',
            command: 'italic',
        }),
        button({
            label: '<u>U</u>',
            title: 'Podkreślenie',
            command: 'underline',
        }),
        button({
            label: '<s>S</s>',
            title: 'Przekreślenie',
            command: 'strikeThrough',
        }),
        button({
            label: 'x<sup>2</sup>',
            title: 'Indeks górny',
            command: 'superscript',
        }),
        button({
            label: 'x<sub>2</sub>',
            title: 'Indeks dolny',
            command: 'subscript',
        }),
        separator(),
        button({
            label: '☰',
            title: 'Lista punktowana',
            command: 'insertUnorderedList',
        }),
        button({
            label: '1.',
            title: 'Lista numerowana',
            command: 'insertOrderedList',
        }),
        button({
            label: '⇤',
            title: 'Zmniejsz wcięcie',
            command: 'outdent',
        }),
        button({
            label: '⇥',
            title: 'Zwiększ wcięcie',
            command: 'indent',
        }),
        separator(),
        button({
            label: '≡←',
            title: 'Wyrównaj do lewej',
            command: 'justifyLeft',
        }),
        button({
            label: '≡',
            title: 'Wyśrodkuj',
            command: 'justifyCenter',
        }),
        button({
            label: '→≡',
            title: 'Wyrównaj do prawej',
            command: 'justifyRight',
        }),
        button({
            label: '☷',
            title: 'Wyjustuj',
            command: 'justifyFull',
        }),
        separator(),
    );

    const colorWrap = document.createElement('label');
    colorWrap.className = 'advanced-wysiwyg-color';
    colorWrap.title = 'Kolor tekstu';
    colorWrap.innerHTML = '<span>A</span>';

    const color = document.createElement('input');
    color.type = 'color';
    color.value = '#26344b';
    color.dataset.editorColor = 'foreColor';
    colorWrap.appendChild(color);

    const backgroundWrap = document.createElement('label');
    backgroundWrap.className = 'advanced-wysiwyg-color';
    backgroundWrap.title = 'Kolor tła tekstu';
    backgroundWrap.innerHTML = '<span>▰</span>';

    const background = document.createElement('input');
    background.type = 'color';
    background.value = '#fff2a8';
    background.dataset.editorColor = 'hiliteColor';
    backgroundWrap.appendChild(background);

    toolbar.append(
        colorWrap,
        backgroundWrap,
        separator(),
        button({
            label: '🔗',
            title: 'Wstaw / edytuj link',
            action: 'link',
        }),
        button({
            label: '▧',
            title: 'Wstaw tabelę',
            action: 'table',
        }),
        button({
            label: '🖼',
            title: 'Wstaw obraz z adresu URL',
            action: 'image',
        }),
    );

    if (document.querySelector('[data-media-picker-modal]')) {
        toolbar.append(
            button({
                label: '▣',
                title: 'Wstaw obraz z Biblioteki mediów',
                action: 'media',
            }),
        );
    }

    toolbar.append(
        button({
            label: '―',
            title: 'Linia pozioma',
            command: 'insertHorizontalRule',
        }),
        separator(),
        button({
            label: 'Tx',
            title: 'Usuń formatowanie',
            command: 'removeFormat',
        }),
        button({
            label: '🧹',
            title: 'Wyczyść formatowanie zaznaczenia',
            action: 'clean',
        }),
        separator(),
        button({
            label: '&lt;/&gt;',
            title: 'Źródło HTML',
            action: 'source',
            className: 'advanced-wysiwyg-source-button',
        }),
        button({
            label: '⛶',
            title: 'Pełny ekran',
            action: 'fullscreen',
        }),
    );

    const sync = () => {
        output.value = state.sourceMode
            ? source.value.trim()
            : editor.innerHTML.trim();

        const text = (
            state.sourceMode
                ? source.value.replace(/<[^>]*>/g, ' ')
                : editor.innerText
        )
            .replace(/\s+/g, ' ')
            .trim();

        state.words.textContent = text
            ? String(text.split(' ').length)
            : '0';

        state.characters.textContent = String(text.length);
    };

    toolbar.addEventListener('mousedown', (event) => {
        if (
            event.target.closest('button')
            || event.target.closest('select')
            || event.target.closest('label')
        ) {
            if (!event.target.matches('select,input[type="color"]')) {
                event.preventDefault();
            }
        }
    });

    toolbar.addEventListener('click', (event) => {
        const control = event.target.closest('button');

        if (!control || state.readonly) {
            return;
        }

        const command = control.dataset.editorCommand;
        const value = control.dataset.editorValue || null;
        const action = control.dataset.editorAction;

        if (command) {
            if (state.sourceMode) {
                return;
            }

            editor.focus();
            document.execCommand(command, false, value);
            sync();
            return;
        }

        if (action === 'link') {
            if (state.sourceMode) {
                return;
            }

            const url = normaliseUrl(
                window.prompt(
                    'Adres URL:',
                    'https://',
                ),
            );

            if (!url) {
                return;
            }

            editor.focus();
            document.execCommand('createLink', false, url);

            editor.querySelectorAll('a[target="_blank"]').forEach((link) => {
                link.setAttribute('rel', 'noopener noreferrer');
            });

            sync();
            return;
        }

        if (action === 'table') {
            if (state.sourceMode) {
                return;
            }

            const rows = Math.max(
                1,
                Math.min(
                    20,
                    Number.parseInt(
                        window.prompt('Liczba wierszy:', '3') || '',
                        10,
                    ) || 0,
                ),
            );

            const columns = Math.max(
                1,
                Math.min(
                    10,
                    Number.parseInt(
                        window.prompt('Liczba kolumn:', '3') || '',
                        10,
                    ) || 0,
                ),
            );

            const header = window.confirm(
                'Czy pierwszy wiersz ma być nagłówkiem tabeli?',
            );

            editor.focus();
            insertHtml(tableMarkup(rows, columns, header));
            sync();
            return;
        }

        if (action === 'image') {
            if (state.sourceMode) {
                return;
            }

            const src = normaliseUrl(
                window.prompt(
                    'Adres obrazu URL:',
                    'https://',
                ),
            );

            if (!src) {
                return;
            }

            const alt = (
                window.prompt(
                    'Tekst alternatywny (ALT):',
                    '',
                ) || ''
            ).replace(/"/g, '&quot;');

            editor.focus();
            insertHtml(
                `<img src="${src.replace(/"/g, '&quot;')}" alt="${alt}">`,
            );
            sync();
            return;
        }

        if (action === 'media') {
            if (state.sourceMode) {
                return;
            }

            const modal = document.querySelector('[data-media-picker-modal]');

            if (!modal) {
                return;
            }

            state.mediaInsertMode = true;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('media-picker-open');

            setTimeout(
                () => modal
                    .querySelector('[data-media-picker-search]')
                    ?.focus(),
                20,
            );

            return;
        }

        if (action === 'clean') {
            if (state.sourceMode) {
                source.value = sanitizePastedHtml(source.value);
            } else {
                editor.innerHTML = sanitizePastedHtml(editor.innerHTML);
            }

            sync();
            return;
        }

        if (action === 'source') {
            state.sourceMode = !state.sourceMode;

            if (state.sourceMode) {
                source.value = editor.innerHTML.trim();
                editor.hidden = true;
                source.hidden = false;
                source.focus();
                control.classList.add('is-active');
            } else {
                editor.innerHTML = sanitizePastedHtml(source.value);
                source.hidden = true;
                editor.hidden = false;
                editor.focus();
                control.classList.remove('is-active');
            }

            wrapper.classList.toggle(
                'is-source-mode',
                state.sourceMode,
            );

            sync();
            return;
        }

        if (action === 'fullscreen') {
            wrapper.classList.toggle('is-fullscreen');
            document.body.classList.toggle(
                'advanced-editor-fullscreen-open',
                wrapper.classList.contains('is-fullscreen'),
            );

            control.classList.toggle(
                'is-active',
                wrapper.classList.contains('is-fullscreen'),
            );
        }
    });

    toolbar.addEventListener('change', (event) => {
        if (state.readonly || state.sourceMode) {
            return;
        }

        const select = event.target.closest('[data-editor-select]');

        if (select) {
            editor.focus();

            if (select.dataset.editorSelect === 'format') {
                document.execCommand(
                    'formatBlock',
                    false,
                    select.value,
                );
            }

            if (
                select.dataset.editorSelect === 'fontSize'
                && select.value
            ) {
                document.execCommand(
                    'fontSize',
                    false,
                    select.value,
                );
            }

            sync();
            return;
        }

        const colorInput = event.target.closest('[data-editor-color]');

        if (colorInput) {
            editor.focus();
            document.execCommand(
                colorInput.dataset.editorColor,
                false,
                colorInput.value,
            );
            sync();
        }
    });

    return {
        toolbar,
        sync,
    };
};

const initEditorMediaIntegration = (states) => {
    const modal = document.querySelector('[data-media-picker-modal]');

    if (!modal) {
        return;
    }

    modal.addEventListener(
        'click',
        (event) => {
            const item = event.target.closest('[data-media-picker-item]');

            if (!item) {
                return;
            }

            const state = [...states].find(
                (candidate) => candidate.mediaInsertMode,
            );

            if (!state) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const url = item.dataset.mediaUrl || '';
            const name = item.dataset.mediaName || '';

            if (url) {
                state.editor.focus();

                insertHtml(
                    `<img src="${url.replace(/"/g, '&quot;')}" alt="${name.replace(/"/g, '&quot;')}">`,
                );

                state.sync();
            }

            state.mediaInsertMode = false;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('media-picker-open');
        },
        true,
    );
};

const initEditor = () => {
    const states = new Set();

    document.querySelectorAll('[data-wysiwyg]').forEach((wrapper) => {
        if (wrapper.dataset.advancedWysiwygMounted === '1') {
            return;
        }

        const editor = wrapper.querySelector('[data-editor]');
        const output = wrapper.querySelector('[data-editor-output]');

        if (!editor || !output) {
            return;
        }

        wrapper.dataset.advancedWysiwygMounted = '1';
        wrapper.classList.add('advanced-wysiwyg');

        const legacyToolbar = wrapper.querySelector('.wysiwyg-toolbar');
        legacyToolbar?.setAttribute('hidden', '');

        const source = document.createElement('textarea');
        source.className = 'advanced-wysiwyg-source';
        source.hidden = true;
        source.spellcheck = false;
        source.setAttribute('aria-label', 'Źródło HTML');
        editor.insertAdjacentElement('afterend', source);

        const status = document.createElement('div');
        status.className = 'advanced-wysiwyg-status';

        const left = document.createElement('div');
        left.className = 'advanced-wysiwyg-status-left';
        left.innerHTML = '<span>WYSIWYG</span><span>HTML</span>';

        const right = document.createElement('div');
        right.className = 'advanced-wysiwyg-status-right';
        right.innerHTML = `
            <span>Słowa: <strong data-editor-words>0</strong></span>
            <span>Znaki: <strong data-editor-characters>0</strong></span>
            <span class="advanced-wysiwyg-resize" aria-hidden="true">◢</span>
        `;

        status.append(left, right);

        const state = {
            wrapper,
            editor,
            output,
            source,
            status,
            words: right.querySelector('[data-editor-words]'),
            characters: right.querySelector('[data-editor-characters]'),
            sourceMode: false,
            readonly: editor.getAttribute('contenteditable') === 'false',
            mediaInsertMode: false,
            sync: () => {},
        };

        const { toolbar, sync } = createAdvancedToolbar(
            wrapper,
            editor,
            output,
            source,
            state,
        );

        state.sync = sync;
        states.add(state);

        legacyToolbar?.insertAdjacentElement('afterend', toolbar);

        if (!legacyToolbar) {
            wrapper.prepend(toolbar);
        }

        wrapper.appendChild(status);

        if (state.readonly) {
            wrapper.classList.add('is-readonly');
            toolbar
                .querySelectorAll(
                    'button,select,input',
                )
                .forEach((control) => {
                    control.disabled = true;
                });
        }

        editor.addEventListener('input', sync);
        source.addEventListener('input', sync);

        editor.addEventListener('paste', (event) => {
            if (state.readonly) {
                event.preventDefault();
                return;
            }

            const clipboard = event.clipboardData;

            if (!clipboard) {
                return;
            }

            const html = clipboard.getData('text/html');
            const text = clipboard.getData('text/plain');

            event.preventDefault();

            if (html) {
                insertHtml(
                    sanitizePastedHtml(html),
                );
            } else {
                insertHtml(
                    text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/\n/g, '<br>'),
                );
            }

            sync();
        });

        editor.closest('form')?.addEventListener('submit', () => {
            if (state.sourceMode) {
                editor.innerHTML = sanitizePastedHtml(source.value);
                state.sourceMode = false;
            }

            output.value = editor.innerHTML.trim();
        });

        wrapper.addEventListener('keydown', (event) => {
            if (
                event.key === 'Escape'
                && wrapper.classList.contains('is-fullscreen')
            ) {
                wrapper.classList.remove('is-fullscreen');
                document.body.classList.remove(
                    'advanced-editor-fullscreen-open',
                );
            }
        });

        sync();
    });

    initEditorMediaIntegration(states);
};

const initStatus = () => {
    const select = document.querySelector('[data-status-select]');
    const dateField = document.querySelector('[data-publication-date]');
    const dateInput = dateField?.querySelector('input');

    if (!select || !dateField || !dateInput) {
        return;
    }

    const update = () => {
        const scheduled = select.value === 'scheduled';
        const published = select.value === 'published';

        dateField.hidden = !(scheduled || published);
        dateInput.required = scheduled;
    };

    select.addEventListener('change', update);
    update();
};

const initImagePreview = () => {
    const input = document.querySelector('[data-image-input]');
    const preview = document.querySelector('[data-image-preview]');
    const image = preview?.querySelector('img');

    if (!input || !preview || !image) {
        return;
    }

    input.addEventListener('change', () => {
        const [file] = input.files;

        if (!file) {
            preview.hidden = true;
            image.removeAttribute('src');
            return;
        }

        image.src = URL.createObjectURL(file);
        preview.hidden = false;
    });
};

const init = () => {
    initSlugGenerator();
    initEditor();
    initStatus();
    initImagePreview();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
