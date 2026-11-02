function togglePass() {
  const inp = document.getElementById('pass-input');
  const icon = document.getElementById('eye-icon');
  if (!inp || !icon) return;
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'fas fa-eye';
  }
}

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const loginBtn = document.getElementById('btn-submit');
    const statusDiv = document.getElementById('status-login');

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

    function handleLogin(e) {
        if (e) e.preventDefault();

        const emailInput = document.getElementById('email_input');
        const passInput = document.getElementById('pass-input');

        const email = emailInput ? emailInput.value.trim() : '';
        const password = passInput ? passInput.value : '';

        if (!email || !password) {
            showError('Email dan password wajib diisi.');
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]');
        if (!token) {
            console.error('CSRF token not found!');
            showError('CSRF token tidak ditemukan. Muat ulang halaman.');
            return;
        }

        if (loginBtn) {
            loginBtn.innerText = 'Processing...';
            loginBtn.disabled = true;
        }
        hideStatus();

        fetch("/login", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token.getAttribute('content')
            },
            body: JSON.stringify({ email, password })
        })
        .then(async response => {
            const contentType = response.headers.get("content-type");
            let data = {};
            if (contentType && contentType.includes("application/json")) {
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
            showSuccess("Berhasil Login! Mengalihkan...");
            const params = new URLSearchParams(window.location.search);
            const next = params.get('redirect');
            const safeNext = next && next.startsWith('/') && !next.startsWith('//') ? next : null;
            setTimeout(() => {
                window.location.href = safeNext || res.redirect || '/';
            }, 600);
        })
        .catch(error => {
            console.error('Login Error:', error);
            showError(error.message || 'Login gagal. Periksa kembali email dan password.');
            if (loginBtn) {
                loginBtn.innerText = 'Sign in';
                loginBtn.disabled = false;
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    } else if (loginBtn) {
        loginBtn.addEventListener('click', handleLogin);
    }

    [document.getElementById('email_input'), document.getElementById('pass-input')].forEach(inp => {
        if (inp) {
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleLogin();
                }
            });
        }
    });
});