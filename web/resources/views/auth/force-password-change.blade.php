@extends('layouts.app')

@section('title', 'Change Temporary Password')
@section('header', 'Change Temporary Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header bg-light"><h5 class="mb-0">Secure your municipality account</h5></div>
            <div class="card-body">
                <p class="text-muted">You are using a temporary password. Change it now to continue to your dashboard.</p>
                <form action="{{ route('account.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Temporary password</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="currentPassword" name="current_password" autocomplete="current-password" required autofocus>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New password</label>
                        <div class="input-group">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="newPassword" name="password" minlength="8" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary" data-password-toggle="newPassword" aria-label="Show new password" aria-pressed="false" title="Show password"><i class="fas fa-eye" aria-hidden="true"></i></button>
                        </div>
                        <div class="form-text">Use at least 8 characters, including an uppercase letter and a symbol.</div>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label for="confirmPassword" class="form-label">Confirm new password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary" data-password-toggle="confirmPassword" aria-label="Show confirmation password" aria-pressed="false" title="Show password"><i class="fas fa-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-lock me-1" aria-hidden="true"></i>Change password and continue</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            const icon = button.querySelector('i');
            const showPassword = input.type === 'password';

            input.type = showPassword ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(showPassword));
            button.setAttribute('aria-label', `${showPassword ? 'Hide' : 'Show'} ${button.dataset.passwordToggle === 'newPassword' ? 'new' : 'confirmation'} password`);
            button.setAttribute('title', `${showPassword ? 'Hide' : 'Show'} password`);
            icon.classList.toggle('fa-eye', !showPassword);
            icon.classList.toggle('fa-eye-slash', showPassword);
        });
    });
</script>
@endsection