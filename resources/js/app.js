import './bootstrap';

document.querySelectorAll('[data-source-filter]').forEach((filter) => {
    const search = filter.querySelector('[data-source-filter-search]');
    const options = [...filter.querySelectorAll('[data-source-filter-option]')];
    const empty = filter.querySelector('[data-source-filter-empty]');

    if (!search) {
        return;
    }

    search.addEventListener('input', () => {
        const term = search.value.trim().toLocaleLowerCase('pt-BR');
        let visible = 0;

        options.forEach((option) => {
            const matches = option.dataset.sourceFilterText.toLocaleLowerCase('pt-BR').includes(term);
            option.classList.toggle('hidden', !matches);
            visible += matches ? 1 : 0;
        });

        empty?.classList.toggle('hidden', visible !== 0);
    });
});

document.querySelectorAll('[data-source-picker]').forEach((picker) => {
    const input = picker.querySelector('[data-source-input]');
    const hidden = picker.querySelector('[data-source-id]');
    const options = picker.querySelector('[data-source-options]');
    const optionButtons = [...picker.querySelectorAll('[data-source-option]')];
    const empty = picker.querySelector('[data-source-empty]');
    const newSourceToggle = picker.querySelector('[data-new-source-toggle]');
    const newSourcePanel = picker.querySelector('[data-new-source-panel]');
    const newSourceName = picker.querySelector('[data-new-source-name]');

    const renderOptions = (term) => {
        const normalized = term.trim().toLocaleLowerCase('pt-BR');
        let visible = 0;

        optionButtons.forEach((option) => {
            const matches = option.dataset.sourceOptionText.toLocaleLowerCase('pt-BR').includes(normalized);
            option.classList.toggle('hidden', !matches);
            visible += matches ? 1 : 0;
        });

        empty?.classList.toggle('hidden', visible !== 0);
        options?.classList.remove('hidden');
    };

    input?.addEventListener('focus', () => renderOptions(input.value));
    input?.addEventListener('input', () => {
        hidden.value = '';
        renderOptions(input.value);
    });

    optionButtons.forEach((option) => option.addEventListener('click', () => {
        input.value = option.dataset.sourceOptionName;
        hidden.value = option.dataset.sourceOptionId;
        options?.classList.add('hidden');
        newSourcePanel?.classList.add('hidden');
        if (newSourceName) {
            newSourceName.value = '';
        }
    }));

    newSourceToggle?.addEventListener('click', () => {
        hidden.value = '';
        input.value = '';
        options?.classList.add('hidden');
        newSourcePanel?.classList.toggle('hidden');
        if (!newSourcePanel?.classList.contains('hidden')) {
            newSourceName?.focus();
        }
    });

    document.addEventListener('click', (event) => {
        if (!picker.contains(event.target)) {
            options?.classList.add('hidden');
        }
    });
});

document.querySelectorAll('[data-feed-picker]').forEach((picker) => {
    const input = picker.querySelector('[data-preset-feed-input]');
    const options = picker.querySelector('[data-preset-feed-options]');
    const optionButtons = [...picker.querySelectorAll('[data-preset-feed-option]')];
    const empty = picker.querySelector('[data-preset-feed-empty]');
    const form = picker.closest('form');
    const nameField = form?.querySelector('[data-feed-name-field]');
    const urlField = form?.querySelector('[data-feed-url-field]');
    const categoryField = form?.querySelector('[data-feed-category-field]');
    const sourcePicker = form?.querySelector('[data-source-picker]');
    const sourceInput = sourcePicker?.querySelector('[data-source-input]');
    const sourceId = sourcePicker?.querySelector('[data-source-id]');
    const newSourcePanel = sourcePicker?.querySelector('[data-new-source-panel]');
    const newSourceName = sourcePicker?.querySelector('[data-new-source-name]');

    const renderOptions = (term) => {
        const normalized = term.trim().toLocaleLowerCase('pt-BR');
        let visible = 0;

        optionButtons.forEach((option) => {
            const searchableText = `${option.dataset.presetFeedName} ${option.dataset.presetFeedSourceName} ${option.dataset.presetFeedUrl}`;
            const matches = searchableText.toLocaleLowerCase('pt-BR').includes(normalized);
            option.classList.toggle('hidden', !matches);
            visible += matches ? 1 : 0;
        });

        empty?.classList.toggle('hidden', visible !== 0);
        options?.classList.remove('hidden');
    };

    input?.addEventListener('focus', () => renderOptions(input.value));
    input?.addEventListener('input', () => renderOptions(input.value));

    optionButtons.forEach((option) => option.addEventListener('click', () => {
        input.value = option.dataset.presetFeedName;
        if (nameField) nameField.value = option.dataset.presetFeedName;
        if (urlField) urlField.value = option.dataset.presetFeedUrl;
        if (categoryField) categoryField.value = option.dataset.presetFeedCategory ?? '';

        if (sourceInput && sourceId) {
            sourceInput.value = option.dataset.presetFeedSourceName;
            sourceId.value = option.dataset.presetFeedSourceId;
        }

        newSourcePanel?.classList.add('hidden');
        if (newSourceName) newSourceName.value = '';
        options?.classList.add('hidden');
    }));

    document.addEventListener('click', (event) => {
        if (!picker.contains(event.target)) {
            options?.classList.add('hidden');
        }
    });
});
