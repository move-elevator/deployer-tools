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
        button.hidden = false;
        button.addEventListener('click', () => {
            const url = new URL(button.dataset.copyUrl, document.baseURI).href;
            navigator.clipboard.writeText(url)
                .then(() => announce(button, 'Copied'))
                .catch(() => announce(button, 'Copy failed'));
        });
    });
}
