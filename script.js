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

    const form = document.getElementById('form');
    const submitBtn = document.getElementById('submit');

    const first_name = document.getElementById('first_name');
    const last_name = document.getElementById('last_name');
    const user_pic = document.getElementById('user_pic');
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
        const ok = first_name.checkValidity();
        setIcon(first_name, ok);
        return ok;
    }

    function checkLastName() {
        const ok = last_name.checkValidity();
        setIcon(last_name, ok);
        return ok;
    }

    function checkEmail() {
        const ok = email.checkValidity();
        setIcon(email, ok);
        return ok;
    }

    function checkPhone() {
        phone.value = phone.value.replace(/[^\d+\s()\-]/g, '');
        const ok = phone.checkValidity();
        setIcon(phone, ok);
        return ok;
    }

    function checkPassword() {
        const ok = password.checkValidity();
        setIcon(password, ok);
        return ok;
    }

    function checkPassword2() {
        const hint = document.getElementById('hint_password2');
        const okLength = password2.value.length >= 6;
        const okMatch = password2.value === password.value && password2.value !== '';

        if (!okLength) {
            password2.setCustomValidity('Пароль должен содержать минимум 6 символов');
            hint.textContent = 'Пароль должен содержать минимум 6 символов';
            hint.style.color = '#d9534f';
            setIcon(password2, false);
            return false;
        }

        if (!okMatch) {
            password2.setCustomValidity('Пароли не совпадают');
            hint.textContent = 'Пароли не совпадают';
            hint.style.color = '#d9534f';
            setIcon(password2, false);
            return false;
        }

        password2.setCustomValidity('');
        hint.textContent = 'Пароли совпадают';
        hint.style.color = '#5cb85c';
        setIcon(password2, true);
        return true;
    }

    function checkFile() {
        const icon = {
            valid: document.getElementById('valid_user_pic'),
            invalid: document.getElementById('invalid_user_pic')
        };
        if (!icon.valid || !icon.invalid) return false;

        if (!user_pic.files || user_pic.files.length === 0) {
            user_pic.setCustomValidity('Выберите изображение профиля');
            icon.valid.style.display = 'none';
            icon.invalid.style.display = 'inline-block';
            return false;
        }

        const file = user_pic.files[0];
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'];
        const maxSize = 2 * 1024 * 1024; // 2 Мб

        if (!allowedTypes.includes(file.type)) {
            user_pic.setCustomValidity('Только форматы jpeg, jpg, png или bmp');
            icon.valid.style.display = 'none';
            icon.invalid.style.display = 'inline-block';
            return false;
        }

        if (file.size > maxSize) {
            user_pic.setCustomValidity('Размер файла не должен превышать 2 Мб');
            icon.valid.style.display = 'none';
            icon.invalid.style.display = 'inline-block';
            return false;
        }

        user_pic.setCustomValidity('');
        icon.valid.style.display = 'inline-block';
        icon.invalid.style.display = 'none';
        return true;
    }
     window.controll = function () {
        const okFirst = checkFirstName();
        const okLast = checkLastName();
        const okEmail = checkEmail();
        const okPhone = checkPhone();
        const okPass = checkPassword();
        const okPass2 = checkPassword2();
        const okFile = checkFile();

        const allValid = okFirst && okLast && okEmail && okPhone && okPass && okPass2 && okFile;

        submitBtn.disabled = !allValid;
        return allValid;
    };

    first_name.addEventListener('input', checkFirstName);
    last_name.addEventListener('input', checkLastName);
    email.addEventListener('input', checkEmail);
    phone.addEventListener('input', checkPhone);
    password.addEventListener('input', function () {
        checkPassword();
        if (password2.value !== '') checkPassword2();
    });
    password2.addEventListener('input', checkPassword2);
    user_pic.addEventListener('change', function () {
        checkFile();
        controll();
    });
    form.addEventListener('submit', function (e) {
        if (!controll()) {
            e.preventDefault();

            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.reportValidity();
            }
        }
    });

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
        controll();
    });
    controll();
});