document.addEventListener('DOMContentLoaded', function() {
    // Password Strength Meter
    const passwordInput = document.querySelector('input[name="new_password"]');
    const strengthMeter = document.querySelector('[data-password-strength]');
    const strengthBar = document.querySelector('[data-strength-bar]');
    const strengthText = document.querySelector('[data-strength-text]');

    if (passwordInput && strengthMeter) {
        passwordInput.addEventListener('input', debounce(function() {
            const password = this.value;
            if (password.length === 0) {
                strengthMeter.classList.add('hidden');
                return;
            }

            strengthMeter.classList.remove('hidden');
            
            // Call API to check strength
            fetch('/branch/account/security/password/strength', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ password: password })
            })
            .then(response => response.json())
            .then(data => {
                updateStrengthUI(data.score);
            })
            .catch(error => {
                console.error('Error checking password strength:', error);
                // Fallback to simple check if API fails
                let score = 0;
                if (password.length >= 8) score += 20;
                if (password.length >= 12) score += 20;
                if (/[A-Z]/.test(password)) score += 15;
                if (/[a-z]/.test(password)) score += 15;
                if (/[0-9]/.test(password)) score += 15;
                if (/[^A-Za-z0-9]/.test(password)) score += 15;
                updateStrengthUI(score);
            });

        }, 500));
    }

    function updateStrengthUI(score) {
        let color = 'bg-rose-500';
        let text = 'Weak';
        let width = Math.min(score, 100) + '%';

        if (score >= 80) {
            color = 'bg-emerald-500';
            text = 'Strong';
        } else if (score >= 50) {
            color = 'bg-yellow-500';
            text = 'Medium';
        }

        strengthBar.className = `h-full transition-all duration-300 ${color}`;
        strengthBar.style.width = width;
        strengthText.textContent = text;
    }

    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Session Revocation
    const revokeButtons = document.querySelectorAll('.revoke-session-btn');
    revokeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to revoke this session?')) {
                e.preventDefault();
            }
        });
    });
});
