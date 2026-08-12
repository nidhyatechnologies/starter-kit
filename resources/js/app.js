import './bootstrap';

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-password-toggle]');

    if (!toggle) {
        return;
    }

    const input = document.querySelector(toggle.dataset.passwordToggle);

    if (!input) {
        return;
    }

    const isVisible = input.type === 'text';

    input.type = isVisible ? 'password' : 'text';
    toggle.textContent = isVisible ? 'Show' : 'Hide';
    toggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
});
