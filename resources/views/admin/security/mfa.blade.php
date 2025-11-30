@extends('admin.layout')

@section('title', 'Two-Factor Authentication')
@section('header', 'Two-Factor Authentication')

@section('content')
<div id="mfa-settings" class="max-w-4xl mx-auto">
    {{-- Status Banner --}}
    <div class="glass-panel p-4 mb-6 {{ $hasMfa ? 'border-l-4 border-emerald-500' : 'border-l-4 border-amber-500' }}">
        <div class="flex items-center gap-3">
            @if($hasMfa)
                <div class="w-10 h-10 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <div>
                    <div class="font-semibold text-emerald-400">Two-Factor Authentication is Enabled</div>
                    <div class="text-sm muted">Your account is protected with an additional layer of security.</div>
                </div>
            @else
                <div class="w-10 h-10 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <div class="font-semibold text-amber-400">Two-Factor Authentication is Not Enabled</div>
                    <div class="text-sm muted">Enable 2FA to add an extra layer of security to your account.</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Registered Devices --}}
    @if($devices->count() > 0)
    <div class="glass-panel p-5 mb-6">
        <h3 class="text-lg font-semibold mb-4">Registered Devices</h3>
        <div class="space-y-3">
            @foreach($devices as $device)
            <div class="flex items-center justify-between p-4 rounded-lg bg-white/5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg {{ $device->is_primary ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-500/20 text-slate-400' }} flex items-center justify-center">
                        @if($device->device_type === 'totp')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        @elseif($device->device_type === 'sms')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        @endif
                    </div>
                    <div>
                        <div class="font-medium">{{ $device->device_name }}</div>
                        <div class="text-xs muted">
                            {{ ucfirst($device->device_type) }}
                            @if($device->is_primary) <span class="text-emerald-400">• Primary</span> @endif
                            @if($device->last_used_at) • Last used {{ $device->last_used_at->diffForHumans() }} @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if(!$device->is_primary)
                    <button onclick="setPrimaryDevice({{ $device->id }})" class="btn btn-sm btn-secondary">Set Primary</button>
                    @endif
                    <button onclick="removeDevice({{ $device->id }})" class="btn btn-sm btn-danger">Remove</button>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4 pt-4 border-t border-white/10">
            <button onclick="showBackupCodes()" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                View Backup Codes
            </button>
        </div>
    </div>
    @endif

    {{-- Setup Options --}}
    <div class="glass-panel p-5">
        <h3 class="text-lg font-semibold mb-4">Add Authentication Method</h3>
        <div class="grid gap-4 md:grid-cols-3">
            {{-- Authenticator App --}}
            <div class="p-4 rounded-lg bg-white/5 hover:bg-white/10 transition cursor-pointer" onclick="setupTotp()">
                <div class="w-12 h-12 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
                <h4 class="font-semibold mb-1">Authenticator App</h4>
                <p class="text-sm muted">Use an app like Google Authenticator or Authy to generate codes.</p>
                <span class="inline-block mt-2 text-xs text-purple-400">Recommended</span>
            </div>

            {{-- SMS --}}
            <div class="p-4 rounded-lg bg-white/5 hover:bg-white/10 transition cursor-pointer" onclick="setupSms()">
                <div class="w-12 h-12 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                </div>
                <h4 class="font-semibold mb-1">SMS Verification</h4>
                <p class="text-sm muted">Receive verification codes via text message.</p>
            </div>

            {{-- Email --}}
            <div class="p-4 rounded-lg bg-white/5 hover:bg-white/10 transition cursor-pointer" onclick="setupEmail()">
                <div class="w-12 h-12 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <h4 class="font-semibold mb-1">Email Verification</h4>
                <p class="text-sm muted">Receive verification codes via email.</p>
            </div>
        </div>
    </div>
</div>

{{-- TOTP Setup Modal --}}
<div id="totp-modal" class="modal hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-semibold">Setup Authenticator App</h3>
            <button onclick="closeModal('totp-modal')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <div class="modal-body">
            <div id="totp-step-1">
                <p class="muted mb-4">Scan this QR code with your authenticator app:</p>
                <div class="flex justify-center mb-4">
                    <div id="qr-code" class="bg-white p-4 rounded-lg">
                        <div class="w-48 h-48 bg-gray-200 animate-pulse"></div>
                    </div>
                </div>
                <p class="text-sm muted mb-2">Or enter this code manually:</p>
                <div class="font-mono text-center bg-white/10 p-3 rounded mb-4" id="manual-key">Loading...</div>
                <button onclick="showTotpVerify()" class="btn btn-primary w-full">Next</button>
            </div>
            <div id="totp-step-2" class="hidden">
                <p class="muted mb-4">Enter the 6-digit code from your authenticator app:</p>
                <input type="text" id="totp-code" class="form-input text-center text-2xl tracking-widest mb-4" maxlength="6" placeholder="000000">
                <input type="text" id="totp-device-name" class="form-input mb-4" placeholder="Device name (optional)">
                <button onclick="verifyTotp()" class="btn btn-primary w-full">Enable 2FA</button>
            </div>
        </div>
    </div>
</div>

{{-- SMS Setup Modal --}}
<div id="sms-modal" class="modal hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-semibold">Setup SMS Verification</h3>
            <button onclick="closeModal('sms-modal')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <div class="modal-body">
            <div id="sms-step-1">
                <p class="muted mb-4">Enter your phone number in international format:</p>
                <input type="tel" id="sms-phone" class="form-input mb-4" placeholder="+1234567890">
                <button onclick="sendSmsCode()" class="btn btn-primary w-full">Send Code</button>
            </div>
            <div id="sms-step-2" class="hidden">
                <p class="muted mb-4">Enter the 6-digit code sent to your phone:</p>
                <input type="text" id="sms-code" class="form-input text-center text-2xl tracking-widest mb-4" maxlength="6" placeholder="000000">
                <button onclick="verifySms()" class="btn btn-primary w-full">Verify & Enable</button>
            </div>
        </div>
    </div>
</div>

{{-- Email Setup Modal --}}
<div id="email-modal" class="modal hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-semibold">Setup Email Verification</h3>
            <button onclick="closeModal('email-modal')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <div class="modal-body">
            <div id="email-step-1">
                <p class="muted mb-4">We'll send a verification code to:</p>
                <p class="font-semibold mb-4">{{ auth()->user()->email }}</p>
                <button onclick="sendEmailCode()" class="btn btn-primary w-full">Send Code</button>
            </div>
            <div id="email-step-2" class="hidden">
                <p class="muted mb-4">Enter the 6-digit code sent to your email:</p>
                <input type="text" id="email-code" class="form-input text-center text-2xl tracking-widest mb-4" maxlength="6" placeholder="000000">
                <button onclick="verifyEmail()" class="btn btn-primary w-full">Verify & Enable</button>
            </div>
        </div>
    </div>
</div>

{{-- Remove Device Modal --}}
<div id="remove-modal" class="modal hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-semibold">Remove MFA Device</h3>
            <button onclick="closeModal('remove-modal')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <div class="modal-body">
            <p class="muted mb-4">Enter your password to confirm removal:</p>
            <input type="password" id="remove-password" class="form-input mb-4" placeholder="Your password">
            <input type="hidden" id="remove-device-id">
            <button onclick="confirmRemoveDevice()" class="btn btn-danger w-full">Remove Device</button>
        </div>
    </div>
</div>

{{-- Backup Codes Modal --}}
<div id="backup-modal" class="modal hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-semibold">Backup Codes</h3>
            <button onclick="closeModal('backup-modal')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <div class="modal-body">
            <div class="bg-amber-500/20 text-amber-400 p-3 rounded mb-4 text-sm">
                <strong>Important:</strong> Store these codes in a safe place. Each code can only be used once.
            </div>
            <div id="backup-codes-list" class="grid grid-cols-2 gap-2 mb-4 font-mono">
                <!-- Codes will be inserted here -->
            </div>
            <div class="flex gap-2">
                <button onclick="copyBackupCodes()" class="btn btn-secondary flex-1">Copy All</button>
                <button onclick="regenerateBackupCodes()" class="btn btn-primary flex-1">Regenerate</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.modal { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal.hidden { display: none; }
.modal-content { background: #1e293b; border-radius: 0.75rem; width: 100%; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
.modal-body { padding: 1.5rem; }
</style>
@endpush

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';
let totpSecret = null;
let backupCodes = [];

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

// TOTP Setup
async function setupTotp() {
    openModal('totp-modal');
    document.getElementById('totp-step-1').classList.remove('hidden');
    document.getElementById('totp-step-2').classList.add('hidden');
    
    try {
        const res = await fetch('{{ route("admin.security.mfa.totp.generate") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const data = await res.json();
        
        if (data.success) {
            totpSecret = data.data.secret;
            document.getElementById('manual-key').textContent = data.data.manual_entry_key;
            if (data.data.qr_code) {
                document.getElementById('qr-code').innerHTML = `<img src="${data.data.qr_code}" alt="QR Code" class="w-48 h-48">`;
            }
        }
    } catch (e) {
        alert('Failed to generate QR code');
    }
}

function showTotpVerify() {
    document.getElementById('totp-step-1').classList.add('hidden');
    document.getElementById('totp-step-2').classList.remove('hidden');
    document.getElementById('totp-code').focus();
}

async function verifyTotp() {
    const code = document.getElementById('totp-code').value;
    const deviceName = document.getElementById('totp-device-name').value;
    
    try {
        const res = await fetch('{{ route("admin.security.mfa.totp.enable") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ code, device_name: deviceName })
        });
        const data = await res.json();
        
        if (data.success) {
            backupCodes = data.backup_codes || [];
            closeModal('totp-modal');
            showBackupCodesAfterSetup();
        } else {
            alert(data.message || 'Verification failed');
        }
    } catch (e) {
        alert('Verification failed');
    }
}

// SMS Setup
function setupSms() {
    openModal('sms-modal');
    document.getElementById('sms-step-1').classList.remove('hidden');
    document.getElementById('sms-step-2').classList.add('hidden');
}

async function sendSmsCode() {
    const phone = document.getElementById('sms-phone').value;
    
    try {
        const res = await fetch('{{ route("admin.security.mfa.sms.setup") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone })
        });
        const data = await res.json();
        
        if (data.success) {
            document.getElementById('sms-step-1').classList.add('hidden');
            document.getElementById('sms-step-2').classList.remove('hidden');
            document.getElementById('sms-code').focus();
        } else {
            alert(data.message || 'Failed to send code');
        }
    } catch (e) {
        alert('Failed to send code');
    }
}

async function verifySms() {
    const code = document.getElementById('sms-code').value;
    
    try {
        const res = await fetch('{{ route("admin.security.mfa.sms.enable") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ code })
        });
        const data = await res.json();
        
        if (data.success) {
            backupCodes = data.backup_codes || [];
            closeModal('sms-modal');
            showBackupCodesAfterSetup();
        } else {
            alert(data.message || 'Verification failed');
        }
    } catch (e) {
        alert('Verification failed');
    }
}

// Email Setup
function setupEmail() {
    openModal('email-modal');
    document.getElementById('email-step-1').classList.remove('hidden');
    document.getElementById('email-step-2').classList.add('hidden');
}

async function sendEmailCode() {
    try {
        const res = await fetch('{{ route("admin.security.mfa.email.setup") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const data = await res.json();
        
        if (data.success) {
            document.getElementById('email-step-1').classList.add('hidden');
            document.getElementById('email-step-2').classList.remove('hidden');
            document.getElementById('email-code').focus();
        } else {
            alert(data.message || 'Failed to send code');
        }
    } catch (e) {
        alert('Failed to send code');
    }
}

async function verifyEmail() {
    const code = document.getElementById('email-code').value;
    
    try {
        const res = await fetch('{{ route("admin.security.mfa.email.enable") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ code })
        });
        const data = await res.json();
        
        if (data.success) {
            backupCodes = data.backup_codes || [];
            closeModal('email-modal');
            showBackupCodesAfterSetup();
        } else {
            alert(data.message || 'Verification failed');
        }
    } catch (e) {
        alert('Verification failed');
    }
}

// Device Management
function removeDevice(deviceId) {
    document.getElementById('remove-device-id').value = deviceId;
    document.getElementById('remove-password').value = '';
    openModal('remove-modal');
}

async function confirmRemoveDevice() {
    const deviceId = document.getElementById('remove-device-id').value;
    const password = document.getElementById('remove-password').value;
    
    try {
        const res = await fetch(`{{ url('admin/security/mfa/devices') }}/${deviceId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ password })
        });
        const data = await res.json();
        
        if (data.success) {
            closeModal('remove-modal');
            location.reload();
        } else {
            alert(data.message || 'Failed to remove device');
        }
    } catch (e) {
        alert('Failed to remove device');
    }
}

async function setPrimaryDevice(deviceId) {
    try {
        const res = await fetch(`{{ url('admin/security/mfa/devices') }}/${deviceId}/primary`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const data = await res.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to set primary');
        }
    } catch (e) {
        alert('Failed to set primary device');
    }
}

// Backup Codes
function showBackupCodesAfterSetup() {
    const list = document.getElementById('backup-codes-list');
    list.innerHTML = backupCodes.map(code => `<div class="bg-white/10 p-2 rounded text-center">${code}</div>`).join('');
    openModal('backup-modal');
}

function showBackupCodes() {
    // Fetch current backup codes
    alert('Enter your password to view backup codes');
    // Implementation would require password verification
}

function copyBackupCodes() {
    navigator.clipboard.writeText(backupCodes.join('\n'));
    alert('Backup codes copied to clipboard');
}

async function regenerateBackupCodes() {
    const password = prompt('Enter your password to regenerate backup codes:');
    if (!password) return;
    
    try {
        const res = await fetch('{{ route("admin.security.mfa.backup-codes") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ password })
        });
        const data = await res.json();
        
        if (data.success) {
            backupCodes = data.backup_codes;
            showBackupCodesAfterSetup();
        } else {
            alert(data.message || 'Failed to regenerate codes');
        }
    } catch (e) {
        alert('Failed to regenerate codes');
    }
}
</script>
@endpush
@endsection
