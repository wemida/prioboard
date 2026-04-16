import './stimulus_bootstrap.js';
import './styles/app.css';

class BoardApp {
    constructor(root) {
        this.root = root;
        this.columnsContainer = root.querySelector('[data-board-columns]');
        this.editable = root.dataset.editable === 'true';
        this.deleteConfirmationEnabled = root.dataset.deleteConfirmation === 'true';
        this.createUrl = root.dataset.createUrl || '';
        this.updateTemplate = root.dataset.cardUpdateTemplate || '';
        this.deleteTemplate = root.dataset.cardDeleteTemplate || '';
        this.moveTemplate = root.dataset.cardMoveTemplate || '';
        this.dragState = null;
        this.activeEditCardId = null;
        this.init();
    }

    init() {
        if (this.root.dataset.initialized === 'true') {
            return;
        }
        this.root.dataset.initialized = 'true';

        if (this.editable) {
            this.bindEditableEvents();
            this.bindDocumentEvents();
        } else {
            initReadonlyRefresh();
        }
    }

    bindEditableEvents() {
        this.columnsContainer.addEventListener('click', (event) => this.handleClick(event));
        this.columnsContainer.addEventListener('keydown', (event) => this.handleKeydown(event));
        this.columnsContainer.addEventListener('dragstart', (event) => this.handleDragStart(event));
        this.columnsContainer.addEventListener('dragenter', (event) => this.handleDragEnter(event));
        this.columnsContainer.addEventListener('dragover', (event) => this.handleDragOver(event));
        this.columnsContainer.addEventListener('drop', (event) => this.handleDrop(event));
        this.columnsContainer.addEventListener('dragend', () => {
            if (!this.dragState?.isDropping) {
                this.clearDragState();
            }
        });
    }

    bindDocumentEvents() {
        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-color-picker]') && !event.target.closest('[data-color-button]')) {
                this.closeAllPickers();
            }
        });
    }

    handleClick(event) {
        const focusButton = event.target.closest('[data-column-focus]');
        if (focusButton) {
            this.focusQuickAdd(focusButton.dataset.columnFocus);
            return;
        }

        const submitButton = event.target.closest('[data-quick-add-submit]');
        if (submitButton) {
            this.submitQuickAdd(submitButton.dataset.quickAddSubmit);
            return;
        }

        const editButton = event.target.closest('[data-card-edit]');
        if (editButton) {
            this.startInlineEdit(editButton.dataset.cardEdit);
            return;
        }

        const saveEditButton = event.target.closest('[data-inline-edit-save]');
        if (saveEditButton) {
            this.saveInlineEdit(saveEditButton.dataset.inlineEditSave);
            return;
        }

        const cancelEditButton = event.target.closest('[data-inline-edit-cancel]');
        if (cancelEditButton) {
            this.cancelInlineEdit(cancelEditButton.dataset.inlineEditCancel);
            return;
        }

        const deleteButton = event.target.closest('[data-card-delete]');
        if (deleteButton) {
            this.deleteCard(deleteButton.dataset.cardDelete);
            return;
        }

        const colorButton = event.target.closest('[data-color-button]');
        if (colorButton) {
            event.stopPropagation();
            this.toggleColorPicker(colorButton);
            return;
        }

        const colorOption = event.target.closest('[data-color-option]');
        if (colorOption) {
            event.stopPropagation();
            this.selectColor(colorOption);
        }
    }

    handleKeydown(event) {
        const quickAddInput = event.target.closest('[data-quick-add-input]');
        if (quickAddInput && event.key === 'Enter') {
            event.preventDefault();
            this.submitQuickAdd(quickAddInput.dataset.quickAddInput, { keepFocus: true });
            return;
        }

        const editInput = event.target.closest('[data-inline-edit-title]');
        if (editInput && event.key === 'Enter') {
            event.preventDefault();
            this.saveInlineEdit(editInput.dataset.inlineEditTitle);
            return;
        }

        if (editInput && event.key === 'Escape') {
            event.preventDefault();
            this.cancelInlineEdit(editInput.dataset.inlineEditTitle);
        }
    }

    handleDragStart(event) {
        const card = event.target.closest('[data-card-id]');
        if (!card || card.classList.contains('board-card-editing')) {
            return;
        }

        this.dragState = {
            cardId: card.dataset.cardId,
            sourceColumn: card.dataset.cardColumn,
            sourcePosition: this.getCardPosition(card),
        };
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.cardId || '');
        window.setTimeout(() => {
            if (this.dragState?.cardId === card.dataset.cardId) {
                const liveCard = this.columnsContainer.querySelector(`[data-card-id="${card.dataset.cardId}"]`);
                if (!liveCard) {
                    return;
                }

                const placeholder = this.ensurePlaceholder(liveCard.parentElement);
                liveCard.parentElement.insertBefore(placeholder, liveCard);
                this.setActiveDropColumn(liveCard.closest('[data-column]'));
                liveCard.classList.add('is-dragging');
            }
        }, 0);
    }

    handleDragEnter(event) {
        const column = event.target.closest('[data-column]');
        if (!column || !this.dragState) {
            return;
        }

        const list = column.querySelector('[data-card-list]');
        if (!list) {
            return;
        }

        this.setActiveDropColumn(column);
        this.ensurePlaceholder(list);
        if (!list.querySelector('[data-drop-placeholder]')) {
            list.appendChild(this.ensurePlaceholder(list));
        }
    }

    handleDragOver(event) {
        const column = event.target.closest('[data-column]');
        if (!column || !this.dragState) {
            return;
        }

        event.preventDefault();
        const list = column.querySelector('[data-card-list]');
        if (!list) {
            return;
        }

        this.setActiveDropColumn(column);
        this.syncDropPlaceholder(list, event.clientY);
    }

    async handleDrop(event) {
        const columnElement = event.target.closest('[data-column]');
        if (!columnElement || !this.dragState) {
            return;
        }

        event.preventDefault();
        const list = columnElement.querySelector('[data-card-list]');
        const column = columnElement.dataset.column;
        if (!list || !column) {
            this.clearDragState();
            return;
        }

        const placeholder = list.querySelector('[data-drop-placeholder]');
        const position = placeholder ? this.getPlaceholderPosition(list, placeholder) : 1;

        if (column === this.dragState.sourceColumn && position === this.dragState.sourcePosition) {
            this.clearDragState();
            return;
        }

        this.dragState.isDropping = true;

        await this.request(
            this.moveTemplate.replace('__ID__', this.dragState.cardId),
            {
                method: 'POST',
                body: { column, position },
            }
        );

        this.clearDragState();
    }

    syncDropPlaceholder(list, clientY) {
        const placeholder = this.ensurePlaceholder(list);
        const cards = [...list.querySelectorAll('[data-card-id]:not(.is-dragging)')];
        let inserted = false;

        cards.forEach((card) => {
            if (inserted) {
                return;
            }

            const rect = card.getBoundingClientRect();
            if (clientY < rect.top + rect.height / 2) {
                list.insertBefore(placeholder, card);
                inserted = true;
            }
        });

        if (!inserted) {
            list.appendChild(placeholder);
        }
    }

    ensurePlaceholder(list) {
        let placeholder = this.columnsContainer.querySelector('[data-drop-placeholder]');
        if (!placeholder) {
            placeholder = document.createElement('div');
            placeholder.className = 'board-card board-card-drop-placeholder';
            placeholder.dataset.dropPlaceholder = 'true';
        }

        if (placeholder.parentElement !== list) {
            list.appendChild(placeholder);
        }

        return placeholder;
    }

    getPlaceholderPosition(list, placeholder) {
        const items = [...list.children].filter((element) =>
            (!element.classList.contains('is-dragging') && element.hasAttribute('data-card-id')) || element.hasAttribute('data-drop-placeholder')
        );

        return Math.max(1, items.indexOf(placeholder) + 1);
    }

    clearDragState() {
        const draggingCard = this.columnsContainer.querySelector('.is-dragging');
        if (draggingCard) {
            draggingCard.classList.remove('is-dragging');
        }

        this.dragState = null;
        this.setActiveDropColumn(null);
        this.columnsContainer.querySelectorAll('[data-drop-placeholder]').forEach((element) => {
            element.remove();
        });
    }

    setActiveDropColumn(column) {
        this.columnsContainer.querySelectorAll('.is-column-drop-target').forEach((element) => {
            if (element !== column) {
                element.classList.remove('is-column-drop-target');
            }
        });

        if (column) {
            column.classList.add('is-column-drop-target');
        }
    }

    getCardPosition(card) {
        const siblings = [...card.parentElement.querySelectorAll('[data-card-id]')];
        return siblings.indexOf(card) + 1;
    }

    focusQuickAdd(column) {
        const input = this.columnsContainer.querySelector(`[data-quick-add-input="${column}"]`);
        if (input) {
            input.focus();
            input.select();
        }
    }

    getSelectedColor(container) {
        const button = container.querySelector('[data-color-button]');
        return button?.dataset.selectedColor || 'neutral';
    }

    setColorButton(button, color) {
        button.dataset.selectedColor = color;
        button.className = `button color-picker-button color-${color}`;
    }

    toggleColorPicker(button) {
        const container = button.closest('.column-quick-add, .inline-edit-form');
        const picker = container?.querySelector('[data-color-picker]');
        if (!picker) {
            return;
        }

        const isHidden = picker.hidden;
        this.closeAllPickers();
        picker.hidden = !isHidden;
    }

    closeAllPickers() {
        this.columnsContainer.querySelectorAll('[data-color-picker]').forEach((picker) => {
            picker.hidden = true;
        });
    }

    selectColor(option) {
        const container = option.closest('.column-quick-add, .inline-edit-form');
        const button = container?.querySelector('[data-color-button]');
        const picker = container?.querySelector('[data-color-picker]');
        if (button) {
            this.setColorButton(button, option.dataset.colorOption || 'neutral');
        }
        if (picker) {
            picker.hidden = true;
        }
    }

    async submitQuickAdd(column, options = {}) {
        const container = this.columnsContainer.querySelector(`[data-column="${column}"] .column-quick-add`);
        const input = container?.querySelector('[data-quick-add-input]');
        if (!container || !input) {
            return;
        }

        const title = input.value.trim();
        if (!title) {
            input.focus();
            return;
        }

        const color = this.getSelectedColor(container);
        await this.request(this.createUrl, {
            method: 'POST',
            body: { title, column, color },
            afterRender: () => {
                const nextContainer = this.columnsContainer.querySelector(`[data-column="${column}"] .column-quick-add`);
                const nextInput = nextContainer?.querySelector('[data-quick-add-input]');
                const nextButton = nextContainer?.querySelector('[data-color-button]');
                if (nextButton) {
                    this.setColorButton(nextButton, color);
                }
                if (options.keepFocus && nextInput) {
                    nextInput.focus();
                }
            },
        });
    }

    startInlineEdit(cardId) {
        this.cancelActiveInlineEdit();

        const card = this.columnsContainer.querySelector(`[data-card-id="${cardId}"]`);
        if (!card) {
            return;
        }

        this.activeEditCardId = cardId;
        const title = this.escapeHtml(card.dataset.cardTitle || '');
        const color = card.dataset.cardColor || 'neutral';
        card.classList.add('board-card-editing');
        card.draggable = false;
        card.innerHTML = `
            <div class="inline-edit-form">
                <div class="quick-add-form inline-edit-row">
                    <input class="quick-add-input" type="text" maxlength="100" value="${title}" data-inline-edit-title="${cardId}">
                    <button class="button color-picker-button color-${color}" type="button" data-color-button data-selected-color="${color}" aria-label="Choose color"></button>
                    <button class="button button-primary icon-button" type="button" data-inline-edit-save="${cardId}" aria-label="Save">✓</button>
                    <button class="button icon-button" type="button" data-inline-edit-cancel="${cardId}" aria-label="Cancel">×</button>
                </div>
                <div class="color-picker" data-color-picker hidden>
                    ${this.renderColorOptions()}
                </div>
            </div>
        `;

        const input = card.querySelector('[data-inline-edit-title]');
        input?.focus();
        input?.select();
    }

    cancelActiveInlineEdit() {
        if (this.activeEditCardId) {
            this.cancelInlineEdit(this.activeEditCardId);
        }
    }

    cancelInlineEdit(cardId) {
        const card = this.columnsContainer.querySelector(`[data-card-id="${cardId}"]`);
        if (!card) {
            this.activeEditCardId = null;
            return;
        }

        this.restoreCardDisplay(card);
        this.activeEditCardId = null;
    }

    restoreCardDisplay(card) {
        const cardId = card.dataset.cardId;
        const title = this.escapeHtml(card.dataset.cardTitle || '');
        card.classList.remove('board-card-editing');
        card.draggable = true;
        card.innerHTML = `
            <p>${this.renderCardIndex(card)} ${title}</p>
            <div class="card-actions">
                <button class="button icon-button" type="button" data-card-edit="${cardId}" aria-label="Edit card">✎</button>
                <button class="button icon-button button-danger" type="button" data-card-delete="${cardId}" aria-label="Delete card">✕</button>
            </div>
        `;
    }

    renderCardIndex(card) {
        const siblings = [...card.parentElement.querySelectorAll('[data-card-id]')];
        const position = siblings.indexOf(card) + 1;
        return `<span class="card-index">${position}.</span>`;
    }

    renderColorOptions() {
        return ['neutral', 'red', 'orange', 'yellow', 'green', 'blue']
            .map((color) => `
                <button class="button color-picker-option color-${color}" type="button" data-color-option="${color}" aria-label="${color}">
                    <span class="color-swatch color-${color}"></span>
                    <span>${this.capitalize(color)}</span>
                </button>
            `)
            .join('');
    }

    capitalize(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    async saveInlineEdit(cardId) {
        const card = this.columnsContainer.querySelector(`[data-card-id="${cardId}"]`);
        const input = card?.querySelector('[data-inline-edit-title]');
        if (!card || !input) {
            return;
        }

        const title = input.value.trim();
        if (!title) {
            input.focus();
            return;
        }

        const color = this.getSelectedColor(card);
        await this.request(this.updateTemplate.replace('__ID__', cardId), {
            method: 'PATCH',
            body: { title, color },
            afterRender: () => {
                this.activeEditCardId = null;
            },
        });
    }

    async deleteCard(cardId) {
        if (this.deleteConfirmationEnabled && !window.confirm('Delete this card?')) {
            return;
        }

        await this.request(this.deleteTemplate.replace('__ID__', cardId), {
            method: 'DELETE',
        });
    }

    async request(url, options) {
        const response = await fetch(url, {
            method: options.method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: options.body ? JSON.stringify(options.body) : undefined,
        });

        const payload = await response.json();
        if (!response.ok) {
            window.alert(payload.error || 'Request failed.');
            return;
        }

        if (payload.html) {
            this.columnsContainer.innerHTML = payload.html;
        }

        if (payload.boardVersion) {
            document.body.dataset.boardVersion = `${payload.boardVersion}`;
        }

        this.closeAllPickers();

        if (typeof options.afterRender === 'function') {
            options.afterRender();
        }
    }

    escapeHtml(value) {
        return value
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }
}

function initReadonlyRefresh() {
    const metaUrl = document.body.dataset.metaUrl;
    const refreshInterval = Number.parseInt(document.body.dataset.refreshInterval || '0', 10);
    const currentBoardVersion = Number.parseInt(document.body.dataset.boardVersion || '0', 10);

    if (!metaUrl || refreshInterval < 10 || currentBoardVersion < 1) {
        return;
    }

    window.setInterval(async () => {
        try {
            const response = await fetch(metaUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            if (Number.parseInt(`${data.boardVersion}`, 10) !== currentBoardVersion) {
                window.location.reload();
            }
        } catch (error) {
            console.debug('Board refresh check failed.', error);
        }
    }, refreshInterval * 1000);
}

document.addEventListener('turbo:load', () => {
    const boardRoot = document.querySelector('[data-board-app]');
    if (boardRoot) {
        new BoardApp(boardRoot);
    }
});
