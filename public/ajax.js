(() => {
    const DUPLICATE_MESSAGE = 'Пользователь с таким email уже существует';
    const CHECK_ERROR_MESSAGE = 'Не удалось проверить email. Попробуйте позже.';

    function initEmailCheck() {
        const form = document.getElementById('form');
        const email = document.getElementById('email');
        const submitBtn = document.getElementById('submit');

        if (!form || !email || !submitBtn) {
            return;
        }

        const validIcon = document.getElementById('valid_email');
        const invalidIcon = document.getElementById('invalid_email');
        const hint = document.getElementById('hint_email');
        const defaultHint = hint ? hint.textContent : '';

        let controller = null;

        function setHint(text, isError) {
            if (!hint) return;

            hint.textContent = text;
            hint.style.color = isError ? '#d9534f' : '';
        }

        function setDuplicateState(isDuplicate) {
            if (isDuplicate) {
                email.setCustomValidity(DUPLICATE_MESSAGE);

                if (validIcon) validIcon.style.display = 'none';
                if (invalidIcon) invalidIcon.style.display = 'inline-block';

                submitBtn.disabled = true;
                setHint(DUPLICATE_MESSAGE, true);
                return;
            }

            email.setCustomValidity('');
            setHint(defaultHint, false);

            if (typeof window.controll === 'function') {
                window.controll();
            } else {
                submitBtn.disabled = !form.checkValidity();
            }
        }

        email.addEventListener('input', () => {
            if (controller) {
                controller.abort();
                controller = null;
            }

            setDuplicateState(false);
        });

        email.addEventListener('blur', async () => {
            const value = email.value.trim();

            if (value === '' || !email.checkValidity()) {
                return;
            }

            if (controller) {
                controller.abort();
            }

            const currentController = new AbortController();
            controller = currentController;

            try {
                const response = await fetch('/check_email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({ email: value }),
                    credentials: 'same-origin',
                    signal: currentController.signal
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const data = await response.json();

                if (currentController.signal.aborted || email.value.trim() !== value) {
                    return;
                }

                if (typeof data.exists !== 'boolean') {
                    throw new Error('Некорректный ответ сервера');
                }

                setDuplicateState(data.exists);
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                setDuplicateState(false);
                setHint(CHECK_ERROR_MESSAGE, true);
            } finally {
                if (controller === currentController) {
                    controller = null;
                }
            }
        });

        form.addEventListener('submit', (event) => {
            if (email.validity.customError && email.validationMessage === DUPLICATE_MESSAGE) {
                event.preventDefault();
                email.focus();
                email.reportValidity();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmailCheck);
    } else {
        initEmailCheck();
    }
})();
