function togglePass(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!inp || !icon) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('register-form');
    const submitBtn = document.getElementById('btn-submit');
    const statusDiv = document.getElementById('status-register');
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');

    function showError(msg) {
        if (!statusDiv) return;
        statusDiv.className = 'error-card';
        statusDiv.innerText = msg;
        statusDiv.style.display = 'block';
    }

    function showSuccess(msg) {
        if (!statusDiv) return;
        statusDiv.className = 'success-card';
        statusDiv.innerText = msg;
        statusDiv.style.display = 'block';
    }

    function hideStatus() {
        if (!statusDiv) return;
        statusDiv.style.display = 'none';
        statusDiv.innerText = '';
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const nameInput = document.getElementById('name_input');
            const emailInput = document.getElementById('email_input');
            const passInput = document.getElementById('pass-input');
            const passConfirmInput = document.getElementById('pass-confirm-input');

            const name = nameInput ? nameInput.value.trim() : '';
            const email = emailInput ? emailInput.value.trim() : '';
            const password = passInput ? passInput.value : '';
            const password_confirmation = passConfirmInput ? passConfirmInput.value : '';

            // Client-side quick validations
            if (!name || !email || !password || !password_confirmation) {
                showError('Semua kolom wajib diisi.');
                return;
            }

            if (password.length < 8) {
                showError('Password minimal harus 8 karakter.');
                return;
            }

            if (password !== password_confirmation) {
                showError('Konfirmasi password tidak cocok.');
                return;
            }

            if (!tokenMeta) {
                console.error('CSRF token not found!');
                showError('CSRF token tidak ditemukan. Muat ulang halaman.');
                return;
            }

            const token = tokenMeta.getAttribute('content');

            submitBtn.innerText = 'Creating account...';
            submitBtn.disabled = true;
            hideStatus();

            fetch('/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                const redirectInput = document.querySelector('input[name="redirect"]');

                body: JSON.stringify({
                    name,
                    email,
                    password,
                    password_confirmation,
                    redirect: redirectInput ? redirectInput.value : null
                })
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                let data = {};
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                }

                if (!response.ok) {
                    let errMsg = data.message || `Error: ${response.status}`;
                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        if (firstKey && data.errors[firstKey].length > 0) {
                            errMsg = data.errors[firstKey][0];
                        }
                    }
                    throw new Error(errMsg);
                }

                return data;
            })
            .then(res => {
                showSuccess('Registrasi berhasil! Mengalihkan...');
                const params = new URLSearchParams(window.location.search);
                const redirectInput = document.querySelector('input[name="redirect"]');
                const next = params.get('redirect') || (redirectInput ? redirectInput.value : null);
                const safeNext = next && next.startsWith('/') && !next.startsWith('//') ? next : null;
                setTimeout(() => {
                    window.location.href = safeNext || res.redirect || '/';
                }, 700);
            })
            .catch(error => {
                console.error('Registration Error:', error);
                showError(error.message || 'Terjadi kesalahan saat mendaftar.');
                if (submitBtn) {
                    submitBtn.innerText = 'Create account';
                    submitBtn.disabled = false;
                }
            });
        });
    }
});
