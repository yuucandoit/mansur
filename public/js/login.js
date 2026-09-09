function togglePass() {
  const inp = document.getElementById('pass-input');
  const icon = document.getElementById('eye-icon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'fas fa-eye';
  }
}

document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.getElementById('btn-submit');

    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            console.log(e);
            e.preventDefault();

            const email = document.getElementById('email_input').value;
            const password = document.getElementById('pass-input').value;
            const errorDiv = document.getElementById('login-error');
            const statusDiv = document.getElementById('status-login');
            
            // Ambil token dari meta tag
            const token = document.querySelector('meta[name="csrf-token"]');

            if (!token) {
                console.error('CSRF token not found! Pastikan ada <meta name="csrf-token" ...>');
                return;
            }

            loginBtn.innerText = 'Processing...';
            loginBtn.disabled = true;

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
                    throw new Error(data.message || `Error: ${response.status}`);
                }
                
                return data;
            })
            .then(res => {
                statusDiv.classList.add('success-card');
                statusDiv.innerText = "Berhasil Login";
                statusDiv.style.display = 'block';
                window.location.href = res.redirect || '/';
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                if (statusDiv) {
                    statusDiv.classList.add('error-card');
                    statusDiv.innerText = error.message;
                    statusDiv.style.display = 'block';
                }

                if (loginBtn) {
                    loginBtn.innerText = 'Sign in';
                    loginBtn.disabled = false;
                }
            });
        });
    }
});