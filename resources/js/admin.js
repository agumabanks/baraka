window.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-overlay]');

    if (sidebarToggle && sidebar && overlay) {
        const toggleSidebar = () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        };

        sidebarToggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    }

    document.querySelectorAll('[data-auto-submit]').forEach((el) => {
        el.addEventListener('change', () => {
            const form = el.closest('form');
            if (form) form.submit();
        });
    });

    // User dropdown functionality (robust for click + hover)
    const userDropdown = document.querySelector('[data-user-dropdown]');
    if (userDropdown) {
        const userToggle = userDropdown.querySelector('[data-user-toggle]');
        const userMenu = userDropdown.querySelector('[data-user-menu]');
        const userIcon = userDropdown.querySelector('[data-user-icon]');

        if (userToggle && userMenu && userIcon) {
            let hideTimeout;

            const openMenu = () => {
                clearTimeout(hideTimeout);
                userMenu.classList.remove('hidden');
                userMenu.classList.add('is-open', 'block');
                userMenu.setAttribute('aria-hidden', 'false');
                userIcon.classList.add('rotate-180');
                console.log('[Branch dropdown] Menu classList after open:', userMenu.className);
            };

            const closeMenu = () => {
                hideTimeout = setTimeout(() => {
                    userMenu.classList.add('hidden');
                    userMenu.classList.remove('is-open', 'block');
                    userMenu.setAttribute('aria-hidden', 'true');
                    userIcon.classList.remove('rotate-180');
                    console.log('[Branch dropdown] Menu classList after close:', userMenu.className);
                }, 120);
            };

            userToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = userMenu.classList.contains('hidden');
                console.log(`[Branch dropdown] Toggle click; currently hidden: ${isHidden}`);
                if (isHidden) {
                    console.log('[Branch dropdown] Opening user menu');
                    openMenu();
                } else {
                    console.log('[Branch dropdown] Closing user menu');
                    closeMenu();
                }
            });

            userToggle.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    userToggle.click();
                }
                if (e.key === 'Escape') {
                    closeMenu();
                }
            });

            userDropdown.addEventListener('mouseenter', openMenu);
            userDropdown.addEventListener('mouseleave', closeMenu);
            userMenu.addEventListener('mouseenter', () => clearTimeout(hideTimeout));
            userMenu.addEventListener('mouseleave', closeMenu);

            document.addEventListener('click', (e) => {
                if (!userDropdown.contains(e.target)) {
                    userMenu.classList.add('hidden');
                    userIcon.classList.remove('rotate-180');
                }
            });
        }
    }

    // ========================================
    // Account Management Features
    // ========================================

    // Profile Image Preview
    const imageInput = document.querySelector('input[name="image"]');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('[data-profile-preview]');
                    if (preview) {
                        preview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Password Strength Indicator
    const newPasswordInput = document.querySelector('input[name="new_password"]');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthContainer = document.querySelector('[data-password-strength]');
            const strengthBar = document.querySelector('[data-strength-bar]');
            const strengthText = document.querySelector('[data-strength-text]');
            
            if (strengthContainer) {
                if (password.length > 0) {
                    strengthContainer.classList.remove('hidden');
                } else {
                    strengthContainer.classList.add('hidden');
                }
            }
            
            if (strengthBar && strengthText) {
                const strength = calculatePasswordStrength(password);
                
                // Update bar width and color
                strengthBar.style.width = strength.percentage + '%';
                strengthBar.className = 'h-full rounded-full transition-all duration-300 ' + strength.colorClass;
                
                // Update text
                strengthText.textContent = strength.label;
                strengthText.className = 'text-xs font-medium ' + strength.textClass;
            }
        });
    }

    function calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        if (score <= 2) {
            return { percentage: 25, label: 'Weak', colorClass: 'bg-rose-500', textClass: 'text-rose-400' };
        } else if (score <= 4) {
            return { percentage: 50, label: 'Fair', colorClass: 'bg-yellow-500', textClass: 'text-yellow-400' };
        } else if (score <= 5) {
            return { percentage: 75, label: 'Good', colorClass: 'bg-blue-500', textClass: 'text-blue-400' };
        } else {
            return { percentage: 100, label: 'Strong', colorClass: 'bg-emerald-500', textClass: 'text-emerald-400' };
        }
    }

    // 2FA QR Code Generation
    const generate2FABtn = document.querySelector('[data-generate-2fa]');
    if (generate2FABtn) {
        generate2FABtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            try {
                const response = await fetch('/branch/account/security/2fa/generate', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                
                if (!response.ok) throw new Error('Failed to generate 2FA');
                
                const data = await response.json();
                
                // Display QR code
                const qrContainer = document.querySelector('[data-qr-code]');
                if (qrContainer) {
                    qrContainer.innerHTML = `<img src="${data.qr_code_url}" alt="QR Code" class="w-48 h-48">`;
                }
                
                // Display secret key
                const secretKey = document.querySelector('[data-secret-key]');
                if (secretKey) {
                    secretKey.textContent = data.secret;
                }
                
                // Store secret in hidden input
                const secretInput = document.querySelector('[data-secret-input]');
                if (secretInput) {
                    secretInput.value = data.secret;
                }
                
            } catch (error) {
                console.error('2FA generation error:', error);
                alert('Failed to generate 2FA code. Please try again.');
            }
        });
    }
});
