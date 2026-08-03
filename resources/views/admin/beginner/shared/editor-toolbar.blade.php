@once
    <style>
        .dxm-editor-toolbar {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 8px;
            align-items: center;
            padding: 8px;
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 16px 16px 0 0;
            background: rgba(2, 6, 23, .58);
        }

        .dxm-editor-toolbar-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 5px;
            align-items: center;
            min-width: 0;
        }

        .dxm-editor-toolbar-row--wide {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 8px;
        }

        .dxm-editor-toolbar-group {
            display: inline-flex;
            flex-wrap: nowrap;
            gap: 5px;
            align-items: center;
            min-width: 0;
            padding-right: 6px;
            margin-right: 1px;
            border-right: 1px solid rgba(255,255,255,.10);
        }

        .dxm-editor-toolbar-group:last-child {
            border-right: 0;
            padding-right: 0;
            margin-right: 0;
        }

        .dxm-editor-tool {
            width: 29px;
            height: 29px;
            flex: 0 0 29px;
            display: inline-grid;
            place-items: center;
            border-radius: 9px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(15, 23, 42, .72);
            color: rgba(255,255,255,.86);
            cursor: pointer;
            transition: .15s ease;
        }

        .dxm-editor-tool:hover {
            transform: translateY(-1px);
            border-color: rgba(34,211,238,.48);
            background: rgba(34,211,238,.12);
            color: #fff;
        }

        .dxm-editor-tool svg {
            width: 15px;
            height: 15px;
            display: block;
            pointer-events: none;
        }

        .dxm-editor-help {
            justify-self: end;
            align-self: center;
            color: rgba(255,255,255,.45);
            font-size: 10px;
            line-height: 1.2;
            white-space: nowrap;
        }

        @media (max-width: 1100px) {
            .dxm-editor-toolbar,
            .dxm-editor-toolbar-row--wide {
                grid-template-columns: 1fr;
            }

            .dxm-editor-toolbar-row {
                flex-wrap: wrap;
            }

            .dxm-editor-tool {
                width: 30px;
                height: 30px;
                flex-basis: 30px;
            }

            .dxm-editor-help {
                justify-self: start;
            }
        }

        @media (max-width: 760px) {
            .dxm-editor-toolbar {
                padding: 7px;
            }

            .dxm-editor-toolbar-group {
                border-right: 0;
                padding-right: 0;
            }

            .dxm-editor-tool {
                width: 30px;
                height: 30px;
                flex-basis: 30px;
            }

            .dxm-editor-help {
                display: none;
            }
        }
    </style>

    <script>
        window.DXMEditorToolbar = window.DXMEditorToolbar || {
            activeEditable: null,

            init() {
                if (this.initialized) return;
                this.initialized = true;

                document.addEventListener('focusin', (event) => {
                    const target = event.target;
                    if (target && (target.matches?.('[contenteditable="true"]') || target.matches?.('textarea'))) {
                        this.activeEditable = target;
                    }
                });

                document.addEventListener('click', (event) => {
                    const button = event.target.closest?.('[data-dxm-editor-command]');
                    if (!button) return;

                    event.preventDefault();

                    const command = button.getAttribute('data-dxm-editor-command');
                    const value = button.getAttribute('data-dxm-editor-value') || null;
                    this.run(command, value, button);
                });
            },

            findEditable(button) {
                const panel = button.closest('.dxm-editor-panel, .dxm-editor, .dxm-form-card, form, section, .dxm-content-studio') || document;
                const localEditable = panel.querySelector('[contenteditable="true"], textarea[name="body_html"], textarea[name="body"], textarea');
                return this.activeEditable || localEditable || document.querySelector('[contenteditable="true"], textarea');
            },

            focusEditable(editable) {
                if (!editable) return;

                editable.focus();

                if (editable.matches('textarea')) {
                    return;
                }

                const selection = window.getSelection();
                if (!selection.rangeCount) {
                    const range = document.createRange();
                    range.selectNodeContents(editable);
                    range.collapse(false);
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
            },

            syncTextarea(editable) {
                if (!editable) return;

                const form = editable.closest('form');
                if (!form) return;

                const hidden = form.querySelector('textarea[name="body_html"], textarea[name="body"]');
                if (hidden && hidden !== editable && editable.matches('[contenteditable="true"]')) {
                    hidden.value = editable.innerHTML;
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                    hidden.dispatchEvent(new Event('change', { bubbles: true }));
                }
            },

            run(command, value, button) {
                const editable = this.findEditable(button);
                this.focusEditable(editable);

                if (!editable) return;

                if (editable.matches('textarea')) {
                    this.runForTextarea(editable, command, value);
                    return;
                }

                switch (command) {
                    case 'createLink': {
                        const url = prompt('Enter the link URL');
                        if (url) document.execCommand('createLink', false, url);
                        break;
                    }

                    case 'insertImage': {
                        const url = prompt('Paste image URL');
                        if (url) document.execCommand('insertImage', false, url);
                        break;
                    }

                    case 'scriptureBlock': {
                        document.execCommand('insertHTML', false, '<blockquote><strong>Scripture Reference</strong><br>Paste scripture text here.</blockquote><p><br></p>');
                        break;
                    }

                    case 'infoBox': {
                        document.execCommand('insertHTML', false, '<div style="border:1px solid rgba(34,211,238,.35);border-radius:14px;padding:14px;background:rgba(34,211,238,.08);"><strong>Important Note</strong><br>Write your note here.</div><p><br></p>');
                        break;
                    }

                    case 'checkList': {
                        document.execCommand('insertHTML', false, '<ul><li>Checklist item</li><li>Checklist item</li></ul>');
                        break;
                    }

                    case 'formatBlock': {
                        document.execCommand('formatBlock', false, value || 'P');
                        break;
                    }

                    default:
                        document.execCommand(command, false, value);
                        break;
                }

                this.syncTextarea(editable);
            },

            runForTextarea(textarea, command, value) {
                const start = textarea.selectionStart || 0;
                const end = textarea.selectionEnd || 0;
                const selected = textarea.value.substring(start, end);
                const before = textarea.value.substring(0, start);
                const after = textarea.value.substring(end);
                let insert = selected;

                const wrap = (left, right = left) => left + (selected || 'text') + right;

                switch (command) {
                    case 'bold': insert = wrap('<strong>', '</strong>'); break;
                    case 'italic': insert = wrap('<em>', '</em>'); break;
                    case 'underline': insert = wrap('<u>', '</u>'); break;
                    case 'strikeThrough': insert = wrap('<s>', '</s>'); break;
                    case 'formatBlock':
                        if (value === 'H2') insert = '<h2>' + (selected || 'Heading') + '</h2>';
                        else if (value === 'H3') insert = '<h3>' + (selected || 'Subheading') + '</h3>';
                        else if (value === 'BLOCKQUOTE') insert = '<blockquote>' + (selected || 'Quote text') + '</blockquote>';
                        else insert = '<p>' + (selected || 'Paragraph') + '</p>';
                        break;
                    case 'insertUnorderedList': insert = '<ul><li>' + (selected || 'List item') + '</li></ul>'; break;
                    case 'insertOrderedList': insert = '<ol><li>' + (selected || 'List item') + '</li></ol>'; break;
                    case 'justifyLeft': insert = '<p style="text-align:left;">' + (selected || 'Text') + '</p>'; break;
                    case 'justifyCenter': insert = '<p style="text-align:center;">' + (selected || 'Text') + '</p>'; break;
                    case 'justifyRight': insert = '<p style="text-align:right;">' + (selected || 'Text') + '</p>'; break;
                    case 'justifyFull': insert = '<p style="text-align:justify;">' + (selected || 'Text') + '</p>'; break;
                    case 'createLink': {
                        const url = prompt('Enter the link URL');
                        if (!url) return;
                        insert = '<a href="' + url + '">' + (selected || url) + '</a>';
                        break;
                    }
                    case 'insertImage': {
                        const url = prompt('Paste image URL');
                        if (!url) return;
                        insert = '<img src="' + url + '" alt="" />';
                        break;
                    }
                    case 'scriptureBlock':
                        insert = '<blockquote><strong>Scripture Reference</strong><br>' + (selected || 'Paste scripture text here.') + '</blockquote>';
                        break;
                    case 'infoBox':
                        insert = '<div class="content-note"><strong>Important Note</strong><br>' + (selected || 'Write your note here.') + '</div>';
                        break;
                    case 'insertHorizontalRule': insert = '<hr>'; break;
                    case 'removeFormat': insert = selected.replace(/<[^>]*>/g, ''); break;
                    default:
                        return;
                }

                textarea.value = before + insert + after;
                textarea.focus();
                textarea.selectionStart = before.length + insert.length;
                textarea.selectionEnd = before.length + insert.length;
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                textarea.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        window.DXMEditorToolbar.init();
    </script>
@endonce

@php
    $icons = [
        'undo' => '<svg viewBox="0 0 24 24" fill="none"><path d="M9 7H5v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 11a7 7 0 1 1 2.1 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'redo' => '<svg viewBox="0 0 24 24" fill="none"><path d="M15 7h4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 11a7 7 0 1 0-2.1 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'bold' => '<svg viewBox="0 0 24 24" fill="none"><path d="M8 5h5a3.5 3.5 0 0 1 0 7H8V5Z" stroke="currentColor" stroke-width="1.8"/><path d="M8 12h6a3.5 3.5 0 0 1 0 7H8v-7Z" stroke="currentColor" stroke-width="1.8"/></svg>',
        'italic' => '<svg viewBox="0 0 24 24" fill="none"><path d="M10 5h8M6 19h8M14 5l-4 14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'underline' => '<svg viewBox="0 0 24 24" fill="none"><path d="M7 5v6a5 5 0 0 0 10 0V5M6 20h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'strike' => '<svg viewBox="0 0 24 24" fill="none"><path d="M7 7.5c1-2 3-3 5.5-3 2.3 0 4 .8 5 2.4M6 12h12M17 16.5c-1 2-3 3-5.4 3-2.5 0-4.4-.9-5.6-2.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'h2' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6v12M12 6v12M4 12h8M15 10.5c.5-1.7 2-2.5 3.7-2.2 1.7.3 2.8 1.5 2.6 3-.1 1.1-.8 1.9-2 2.8L16 17h5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'h3' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6v12M12 6v12M4 12h8M16 8.5h5l-3 3a3 3 0 1 1-2.5 4.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'paragraph' => '<svg viewBox="0 0 24 24" fill="none"><path d="M13 20V5M17 20V5M18 5H9.5a4 4 0 0 0 0 8H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'quote' => '<svg viewBox="0 0 24 24" fill="none"><path d="M8 10H5.5C5.8 7 7.2 5.2 10 4.5l.7 1.6C9.4 6.6 8.7 7.4 8.5 8.7H11V15H5v-5h3Zm10 0h-2.5c.3-3 1.7-4.8 4.5-5.5l.7 1.6c-1.3.5-2 1.3-2.2 2.6H21V15h-6v-5h3Z" fill="currentColor"/></svg>',
        'ul' => '<svg viewBox="0 0 24 24" fill="none"><path d="M9 7h11M9 12h11M9 17h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="4.5" cy="7" r="1.3" fill="currentColor"/><circle cx="4.5" cy="12" r="1.3" fill="currentColor"/><circle cx="4.5" cy="17" r="1.3" fill="currentColor"/></svg>',
        'ol' => '<svg viewBox="0 0 24 24" fill="none"><path d="M10 7h10M10 12h10M10 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M4 6h1v3M3.7 9h2.6M4 12h2l-2 3h2M4 17.2h1.4a1 1 0 0 1 0 2H4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'check' => '<svg viewBox="0 0 24 24" fill="none"><path d="M10 7h10M10 12h10M10 17h10M3.5 7l1.4 1.4L7.5 5.8M3.5 12l1.4 1.4 2.6-2.6M3.5 17l1.4 1.4 2.6-2.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'left' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 10h11M4 14h16M4 18h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'center' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M7 10h10M4 14h16M7 18h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'right' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M9 10h11M4 14h16M9 18h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'justify' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'indent' => '<svg viewBox="0 0 24 24" fill="none"><path d="M11 7h9M11 12h9M11 17h9M4 8l3.5 4L4 16V8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'outdent' => '<svg viewBox="0 0 24 24" fill="none"><path d="M11 7h9M11 12h9M11 17h9M8 8l-3.5 4L8 16V8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'link' => '<svg viewBox="0 0 24 24" fill="none"><path d="M10.5 13.5a4 4 0 0 0 5.7 0l2-2a4 4 0 0 0-5.7-5.7l-1 1M13.5 10.5a4 4 0 0 0-5.7 0l-2 2a4 4 0 0 0 5.7 5.7l1-1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'image' => '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="5" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 17l4-4a1.4 1.4 0 0 1 2 0l2 2 1.5-1.5a1.4 1.4 0 0 1 2 0L20 17" stroke="currentColor" stroke-width="1.8"/><circle cx="16" cy="9" r="1.2" fill="currentColor"/></svg>',
        'hr' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 12h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'scripture' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6 4h9l3 3v13H6V4Z" stroke="currentColor" stroke-width="1.8"/><path d="M15 4v4h4M9 11h6M9 14h6M12 9v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'box' => '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="5" width="16" height="14" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'clear' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6 19h12M8 5h8l-1 6H9L8 5ZM9 11l-2 5h10l-2-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];
@endphp

<div class="dxm-editor-toolbar" role="toolbar" aria-label="Content editor toolbar">
    <div class="dxm-editor-toolbar-row">
        <div class="dxm-editor-toolbar-group">
            <button type="button" class="dxm-editor-tool" title="Undo" aria-label="Undo" data-dxm-editor-command="undo">{!! $icons['undo'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Redo" aria-label="Redo" data-dxm-editor-command="redo">{!! $icons['redo'] !!}</button>
        </div>

        <div class="dxm-editor-toolbar-group">
            <button type="button" class="dxm-editor-tool" title="Paragraph" aria-label="Paragraph" data-dxm-editor-command="formatBlock" data-dxm-editor-value="P">{!! $icons['paragraph'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Heading 2" aria-label="Heading 2" data-dxm-editor-command="formatBlock" data-dxm-editor-value="H2">{!! $icons['h2'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Heading 3" aria-label="Heading 3" data-dxm-editor-command="formatBlock" data-dxm-editor-value="H3">{!! $icons['h3'] !!}</button>
        </div>

        <div class="dxm-editor-toolbar-group">
            <button type="button" class="dxm-editor-tool" title="Bold" aria-label="Bold" data-dxm-editor-command="bold">{!! $icons['bold'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Italic" aria-label="Italic" data-dxm-editor-command="italic">{!! $icons['italic'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Underline" aria-label="Underline" data-dxm-editor-command="underline">{!! $icons['underline'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Strikethrough" aria-label="Strikethrough" data-dxm-editor-command="strikeThrough">{!! $icons['strike'] !!}</button>
        </div>

        <div class="dxm-editor-toolbar-group">
            <button type="button" class="dxm-editor-tool" title="Bullet list" aria-label="Bullet list" data-dxm-editor-command="insertUnorderedList">{!! $icons['ul'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Numbered list" aria-label="Numbered list" data-dxm-editor-command="insertOrderedList">{!! $icons['ol'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Checklist" aria-label="Checklist" data-dxm-editor-command="checkList">{!! $icons['check'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Quote block" aria-label="Quote block" data-dxm-editor-command="formatBlock" data-dxm-editor-value="BLOCKQUOTE">{!! $icons['quote'] !!}</button>
        </div>
    </div>

    <div class="dxm-editor-toolbar-row">
        <div class="dxm-editor-toolbar-group">
            <button type="button" class="dxm-editor-tool" title="Align left" aria-label="Align left" data-dxm-editor-command="justifyLeft">{!! $icons['left'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Align center" aria-label="Align center" data-dxm-editor-command="justifyCenter">{!! $icons['center'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Align right" aria-label="Align right" data-dxm-editor-command="justifyRight">{!! $icons['right'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Justify" aria-label="Justify" data-dxm-editor-command="justifyFull">{!! $icons['justify'] !!}</button>
        </div>

        <div class="dxm-editor-toolbar-group">
            <button type="button" class="dxm-editor-tool" title="Outdent" aria-label="Outdent" data-dxm-editor-command="outdent">{!! $icons['outdent'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Indent" aria-label="Indent" data-dxm-editor-command="indent">{!! $icons['indent'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Horizontal line" aria-label="Horizontal line" data-dxm-editor-command="insertHorizontalRule">{!! $icons['hr'] !!}</button>
        </div>

        <div class="dxm-editor-toolbar-group">
            <button type="button" class="dxm-editor-tool" title="Insert link" aria-label="Insert link" data-dxm-editor-command="createLink">{!! $icons['link'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Insert image by URL" aria-label="Insert image by URL" data-dxm-editor-command="insertImage">{!! $icons['image'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Scripture block" aria-label="Scripture block" data-dxm-editor-command="scriptureBlock">{!! $icons['scripture'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Info box" aria-label="Info box" data-dxm-editor-command="infoBox">{!! $icons['box'] !!}</button>
            <button type="button" class="dxm-editor-tool" title="Clear formatting" aria-label="Clear formatting" data-dxm-editor-command="removeFormat">{!! $icons['clear'] !!}</button>
        </div>

        <div class="dxm-editor-help">SVG toolbar · hover each icon</div>
    </div>
</div>
