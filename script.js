const burger = document.querySelector('.burger');
        const menu = document.querySelector('.header-list');

        burger.addEventListener('click', () => {
            burger.classList.toggle('open');
            menu.classList.toggle('open');
            document.body.classList.toggle('no-scroll');
        });

        menu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                burger.classList.remove('open');
                menu.classList.remove('open');
                document.body.classList.remove('no-scroll');
            });
        });

document.addEventListener('DOMContentLoaded', function () {

    function initCookieBanner() {
        const consentName = 'cookie_consent';

        if (document.cookie.split('; ').some(item => item.startsWith(consentName + '='))) {
            return;
        }

        const banner = document.createElement('div');
        banner.className = 'cookie-banner';
        banner.innerHTML = '<p>Мы используем cookie для работы сайта. Продолжая, вы соглашаетесь с их использованием.</p>' +
            '<button type="button" class="cookie-accept">Принять</button>';

        document.body.appendChild(banner);

        banner.querySelector('.cookie-accept').addEventListener('click', () => {
            document.cookie = consentName + '=1; path=/; max-age=31536000; SameSite=Lax';
            banner.remove();
        });
    }

    function initPhotoUpload() {
        const form = document.getElementById('photo_form');
        const input = document.getElementById('photo');
        const message = document.getElementById('photo_message');

        if (!form || !input || !message) {
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024;

        function showMessage(text, isError) {
            message.textContent = text;
            message.classList.toggle('photo-upload-message--error', Boolean(isError));
        }

        input.addEventListener('change', () => {
            showMessage('', false);

            if (!input.files || input.files.length === 0) {
                showMessage('Выберите изображение', true);
                return;
            }

            const file = input.files[0];

            if (!allowedTypes.includes(file.type)) {
                showMessage('Допустимы только JPG, PNG, GIF и WebP', true);
                input.value = '';
                return;
            }

            if (file.size > maxSize) {
                showMessage('Размер файла не должен превышать 5 МБ', true);
                input.value = '';
                return;
            }

            showMessage('Файл выбран: ' + file.name, false);
        });

        form.addEventListener('submit', (event) => {
            if (!input.files || input.files.length === 0) {
                event.preventDefault();
                showMessage('Выберите изображение', true);
            }
        });
    }

    const form = document.getElementById('form');
    const submitBtn = document.getElementById('submit');

    if (!form || !submitBtn) {
        initCookieBanner();
        initPhotoUpload();
        return;
    }

    const firstName = document.getElementById('first_name');
    const lastName = document.getElementById('last_name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const password = document.getElementById('password');
    const password2 = document.getElementById('password2');

    function setIcon(input, isValid) {
        const validIcon = document.getElementById('valid_' + input.id);
        const invalidIcon = document.getElementById('invalid_' + input.id);

        if (!validIcon || !invalidIcon) return;

        if (input.value === '') {
            validIcon.style.display = 'none';
            invalidIcon.style.display = 'none';
            return;
        }

        if (isValid) {
            validIcon.style.display = 'inline-block';
            invalidIcon.style.display = 'none';
        } else {
            validIcon.style.display = 'none';
            invalidIcon.style.display = 'inline-block';
        }
    }

    function checkFirstName() {
        const ok = Boolean(firstName && firstName.checkValidity());
        if (firstName) setIcon(firstName, ok);
        return ok;
    }

    function checkLastName() {
        const ok = Boolean(lastName && lastName.checkValidity());
        if (lastName) setIcon(lastName, ok);
        return ok;
    }

    function checkEmail() {
        const ok = Boolean(email && email.checkValidity());
        if (email) setIcon(email, ok);
        return ok;
    }

    function checkPhone() {
        const ok = Boolean(phone && phone.checkValidity());
        if (phone) setIcon(phone, ok);
        return ok;
    }

    function checkPassword() {
        const ok = Boolean(password && password.checkValidity());
        if (password) setIcon(password, ok);
        return ok;
    }

    function checkPassword2() {
        const hint = document.getElementById('hint_password2');

        if (!password2) {
            return false;
        }

        const okLength = password2.value.length >= 6;
        const okMatch = password2.value === password.value && password2.value !== '';

        if (!okLength) {
            password2.setCustomValidity('Пароль должен содержать минимум 6 символов');
            if (hint) {
                hint.textContent = 'Пароль должен содержать минимум 6 символов';
                hint.style.color = '#d9534f';
            }
            setIcon(password2, false);
            return false;
        }

        if (!okMatch) {
            password2.setCustomValidity('Пароли не совпадают');
            if (hint) {
                hint.textContent = 'Пароли не совпадают';
                hint.style.color = '#d9534f';
            }
            setIcon(password2, false);
            return false;
        }

        password2.setCustomValidity('');
        if (hint) {
            hint.textContent = 'Пароли совпадают';
            hint.style.color = '#5cb85c';
        }
        setIcon(password2, true);
        return true;
    }

    window.controll = function () {
        const okFirst = checkFirstName();
        const okLast = checkLastName();
        const okEmail = checkEmail();
        const okPhone = checkPhone();
        const okPass = checkPassword();
        const okPass2 = checkPassword2();

        const allValid = okFirst && okLast && okEmail && okPhone && okPass && okPass2;

        submitBtn.disabled = !allValid;
        return allValid;
    };

    if (firstName) firstName.addEventListener('input', checkFirstName);
    if (lastName) lastName.addEventListener('input', checkLastName);
    if (email) email.addEventListener('input', checkEmail);
    if (phone) phone.addEventListener('input', checkPhone);
    if (password) {
        password.addEventListener('input', function () {
            checkPassword();
            if (password2 && password2.value !== '') checkPassword2();
        });
    }
    if (password2) password2.addEventListener('input', checkPassword2);

    if (phone) {
        phone.addEventListener('input', function () {
            let digits = phone.value.replace(/\D/g, '');

            if (digits.startsWith('8')) digits = '7' + digits.slice(1);
            if (!digits.startsWith('7')) digits = '7' + digits;

            digits = digits.slice(0, 11);

            let formatted = '+7';
            if (digits.length > 1) formatted += ' (' + digits.slice(1, 4);
            if (digits.length >= 4) formatted += ') ' + digits.slice(4, 7);
            if (digits.length >= 7) formatted += '-' + digits.slice(7, 9);
            if (digits.length >= 9) formatted += '-' + digits.slice(9, 11);

            phone.value = formatted;
            checkPhone();
            window.controll();
        });
    }

    form.addEventListener('submit', function (event) {
        if (!window.controll()) {
            event.preventDefault();

            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.reportValidity();
            }
        }
    });

    initCookieBanner();
    initPhotoUpload();
    window.controll();
});
