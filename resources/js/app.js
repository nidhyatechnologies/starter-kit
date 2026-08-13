import './bootstrap';

const installLivewireProgress = () => {
    if (window.__nidhyaLivewireProgressInstalled) {
        return;
    }

    window.__nidhyaLivewireProgressInstalled = true;

    const progress = document.createElement('div');
    progress.className = 'app-request-progress';
    progress.setAttribute('aria-hidden', 'true');

    let finishTimer;

    const ensureProgress = () => {
        if (!progress.isConnected) {
            document.body.append(progress);
        }
    };

    const start = () => {
        ensureProgress();
        window.clearTimeout(finishTimer);
        progress.classList.add('is-active');

        window.requestAnimationFrame(() => progress.classList.add('is-progressing'));
    };

    const finish = () => {
        window.clearTimeout(finishTimer);
        progress.classList.remove('is-progressing');
        finishTimer = window.setTimeout(() => progress.classList.remove('is-active'), 180);
    };

    const syncWithLivewire = () => {
        if (document.querySelector('[data-loading]')) {
            start();

            return;
        }

        finish();
    };

    new MutationObserver(syncWithLivewire).observe(document.documentElement, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['data-loading'],
    });

    document.addEventListener('livewire:navigate', start);
    document.addEventListener('livewire:navigated', finish);
};

installLivewireProgress();

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
