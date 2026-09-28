// The copy buttons are hidden in the markup, since they only work with JavaScript and a secure context
if (navigator.clipboard) {
    const status = document.createElement('span');
    status.className = 'visually-hidden';
    status.setAttribute('role', 'status');
    document.body.append(status);

    const announce = (button, message) => {
        button.textContent = message;
        status.textContent = message;
        setTimeout(() => { button.textContent = 'Copy URL'; }, 1500);
    };

    document.querySelectorAll('[data-copy-url]').forEach((button) => {
        button.parentElement.hidden = false;
        button.addEventListener('click', () => {
            const url = new URL(button.dataset.copyUrl, document.baseURI).href;
            navigator.clipboard.writeText(url)
                .then(() => announce(button, 'Copied'))
                .catch(() => announce(button, 'Copy failed'));
        });
    });
}

const filter = document.getElementById('instance-filter');
if (filter) {
    const instances = [...document.querySelectorAll('.instance')];
    const groups = [...document.querySelectorAll('.instance-group')];
    const empty = document.querySelector('.filter-empty');
    filter.closest('label').hidden = false;
    filter.addEventListener('input', () => {
        const query = filter.value.trim().toLowerCase();
        instances.forEach((instance) => { instance.hidden = !instance.dataset.search.includes(query); });
        groups.forEach((group) => { group.hidden = query !== '' && !group.querySelector('.instance:not([hidden])'); });
        empty.hidden = instances.some((instance) => !instance.hidden);
    });
}
