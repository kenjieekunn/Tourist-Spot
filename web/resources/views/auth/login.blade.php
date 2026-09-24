<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Tourist Spot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #173f43 0%, #0f766e 58%, #3f7d42 100%);
            background-image: url('https://www.discoverthephilippines.com/wp-content/uploads/2021/08/article-cover-photo-pangasinan-guide-810x421.jpg');
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
            background-position: center center;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
            pointer-events: none;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 100;
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(220, 233, 229, 0.9);
        }

        .login-header {
            background: linear-gradient(135deg, #173f43 0%, #0f766e 68%, #3f7d42 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M0,50 Q300,0 600,50 T1200,50 L1200,120 L0,120 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
            opacity: 0.5;
        }

        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            font-weight: 700;
        }

        .login-header p {
            font-size: 0.95rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            letter-spacing: 0.5px;
        }

        .login-body {
            padding: 2.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: #0f766e;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.9rem 1.2rem;
            border: 2px solid #e0e0e0;
            background-color: #fafafa;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #0f766e;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
        }

        .form-control::placeholder {
            color: #999;
        }

        .btn-login {
            background: linear-gradient(135deg, #ff6b35 0%, #e55a2b 100%);
            border: none;
            padding: 1rem;
            font-weight: 700;
            border-radius: 8px;
            width: 100%;
            color: white;
            transition: all 0.3s ease;
            font-size: 1rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
            color: white;
        }

        .password-toggle {
            border: 2px solid #e0e0e0;
            border-left: 0;
            background: #fafafa;
            color: #0f766e;
            padding: 0 1rem;
        }

        .password-toggle:hover,
        .password-toggle:focus {
            background: #e5f5f1;
            border-color: #0f766e;
            color: #115e59;
            box-shadow: none;
        }

        .password-toggle:focus-visible {
            outline: 3px solid rgba(15, 118, 110, 0.2);
            outline-offset: 1px;
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .login-footer {
            text-align: center;
            padding: 1.5rem 2rem;
            background-color: rgba(248, 249, 250, 0.9);
            color: #666;
            font-size: 0.95rem;
            border-top: 1px solid #f0f0f0;
        }

        .login-footer a {
            color: #0f766e;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .login-footer a:hover {
            color: #115e59;
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
            border: none;
            animation: slideInDown 0.4s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }

        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                margin: 1rem;
            }

            .login-header {
                padding: 2rem 1.5rem;
            }

            .login-header h1 {
                font-size: 1.6rem;
            }

            .login-body {
                padding: 1.5rem;
            }

            .login-footer {
                padding: 1rem 1.5rem;
            }
        }

        /* Fallback gradient if background image fails to load */
        @supports not (background-attachment: fixed) {
            body::before {
                background-attachment: scroll;
            }
        }

        .brand-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h1>2nd District of Pangasinan</h1>
                <p>Admin Dashboard</p>
            </div>

            <div class="login-body">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-exclamation-circle"></i> Login Failed!</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="login" class="form-label">
                            <i class="fas fa-user"></i> Email/Username
                        </label>
                        <input 
                            type="text" 
                            class="form-control @error('login') is-invalid @enderror" 
                            id="login" 
                            name="login" 
                            value="{{ old('login') }}" 
                            placeholder="superadmin or admin@example.com"
                            required 
                            autofocus
                        >
                        @error('login')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="btn password-toggle" id="toggle-password" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>


                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    <script>
        // Handle background image loading with proper error handling
        document.addEventListener('DOMContentLoaded', function() {
            const bgImageUrl = 'https://www.discoverthephilippines.com/wp-content/uploads/2021/08/article-cover-photo-pangasinan-guide-810x421.jpg';
            
            // Test if image can be loaded
            const img = new Image();
            
            img.onload = function() {
                console.log('✓ Background image loaded successfully');
                // Image loaded successfully - the CSS background-image should display
                document.body.style.backgroundImage = `url('${bgImageUrl}')`;
            };
            
            img.onerror = function() {
                console.error('✗ Background image failed to load - using fallback gradient');
                // Fallback to gradient if image fails
                document.body.style.backgroundImage = 'none';
                document.body.style.background = 'linear-gradient(135deg, #173f43 0%, #0f766e 58%, #3f7d42 100%)';
            };
            
            img.src = bgImageUrl;
            
            // Log background info
            console.log('Background Image URL:', bgImageUrl);

            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('toggle-password');

            passwordToggle.addEventListener('click', function() {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                passwordToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                passwordToggle.setAttribute('aria-pressed', String(isHidden));
                passwordToggle.querySelector('i').className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
            });
        });
    </script>
</body>
</html>
