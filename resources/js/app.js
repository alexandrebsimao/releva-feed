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

document.querySelectorAll('[data-infinite-feed]').forEach((feed) => {
    const list = feed.querySelector('[data-feed-list]');
    const pagination = feed.querySelector('[data-feed-pagination]');
    const sentinel = feed.querySelector('[data-feed-sentinel]');
    const nextLink = pagination?.querySelector('a[rel="next"]');

    if (!list || !pagination || !sentinel || !nextLink || !('IntersectionObserver' in window)) {
        return;
    }

    let nextUrl = nextLink.href;
    let loading = false;

    const loadNextPage = async () => {
        if (loading || !nextUrl) {
            return;
        }

        loading = true;
        sentinel.textContent = 'Carregando mais artigos…';

        try {
            const response = await fetch(nextUrl, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Feed request failed with status ${response.status}`);
            }

            const html = await response.text();
            const documentFragment = new DOMParser().parseFromString(html, 'text/html');
            const incomingList = documentFragment.querySelector('[data-feed-list]');
            const incomingPagination = documentFragment.querySelector('[data-feed-pagination]');

            if (!incomingList || !incomingPagination) {
                throw new Error('Feed response did not contain the expected markup');
            }

            const existingKeys = new Set(
                [...list.querySelectorAll('[data-feed-item-key]')].map((item) => item.dataset.feedItemKey),
            );

            [...incomingList.children].forEach((item) => {
                const key = item.dataset.feedItemKey;

                if (!key || !existingKeys.has(key)) {
                    list.append(item);
                    if (key) {
                        existingKeys.add(key);
                    }
                }
            });

            const incomingNextLink = incomingPagination.querySelector('a[rel="next"]');
            nextUrl = incomingNextLink?.href ?? '';
            pagination.innerHTML = incomingPagination.innerHTML;
            sentinel.textContent = nextUrl ? 'Role para carregar mais.' : 'Todos os artigos foram carregados.';
        } catch (error) {
            console.error(error);
            sentinel.textContent = 'Não foi possível carregar mais artigos.';
            pagination.classList.remove('hidden');
        } finally {
            loading = false;
        }
    };

    pagination.classList.add('hidden');
    const observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            loadNextPage();
        }
    }, { rootMargin: '600px 0px' });

    observer.observe(sentinel);
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-save-item]');

    if (!button) {
        return;
    }

    const saved = button.dataset.saved === 'true';
    button.dataset.saved = saved ? 'false' : 'true';
    button.setAttribute('aria-label', saved ? 'Salvar para ler depois' : 'Remover dos salvos');
});

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((error) => {
            console.error('Não foi possível ativar o modo app.', error);
        });
    });
}
