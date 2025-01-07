<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . url('index.php'));
    exit();
}

$pageTitle = 'Login/Register - ' . SITE_NAME;
require_once 'header.php';
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-sm">
                <!-- Nav tabs -->
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" 
                                    data-bs-target="#login" type="button" role="tab" aria-selected="true">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="register-tab" data-bs-toggle="tab" 
                                    data-bs-target="#register" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-user-plus me-2"></i>Register
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content">
                        <!-- Login Tab -->
                        <div class="tab-pane fade show active" id="login" role="tabpanel">
                            <h1 class="h4 mb-4">Welcome Back!</h1>
                            <form id="login-form" novalidate>
                                <div class="mb-3">
                                    <label for="login-email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="login-email" name="email" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="login-password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="login-password" name="password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="login-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Please enter your password.</div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </form>
                        </div>

                        <!-- Register Tab -->
                        <div class="tab-pane fade" id="register" role="tabpanel">
                            <h1 class="h4 mb-4">Create Account</h1>
                            <form id="register-form" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                        <div class="invalid-feedback">Please enter your first name.</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                        <div class="invalid-feedback">Please enter your last name.</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="register-email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="register-email" name="email" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                    <div class="invalid-feedback">Please enter a valid phone number.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Delivery Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                                    <div class="invalid-feedback">Please enter your delivery address.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="register-password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="register-password" name="password" 
                                               pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="register-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Password must be at least 8 characters long and include both letters and numbers.</div>
                                    <div class="invalid-feedback">Please enter a valid password.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Passwords do not match.</div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Login form submission
    document.getElementById('login-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        try {
            const formData = new FormData(this);
            const response = await fetch('<?= url('php/controllers/auth_controller.php?action=login') ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();
            
            if (result.success) {
                // Store session data in localStorage for redundancy
                localStorage.setItem('user_id', result.data.user_id);
                showNotification(result.message, 'success');
                window.location.href = result.data.redirect;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            showNotification(error.message, 'error');
        }
    });

    // Register form submission
    document.getElementById('register-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        // Check if passwords match
        const password = this.querySelector('#register-password').value;
        const confirmPassword = this.querySelector('#confirm_password').value;
        
        if (password !== confirmPassword) {
            this.querySelector('#confirm_password').setCustomValidity('Passwords do not match');
            this.classList.add('was-validated');
            return;
        }

        try {
            const formData = new FormData(this);
            const response = await fetch('<?= url('php/controllers/auth_controller.php?action=register') ?>', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                showNotification(result.message, 'success');
                window.location.href = result.data.redirect;
            } else {
                if (result.errors) {
                    Object.keys(result.errors).forEach(field => {
                        const input = this.querySelector(`#${field}`);
                        if (input) {
                            input.setCustomValidity(result.errors[field]);
                            input.reportValidity();
                        }
                    });
                }
                throw new Error(result.message);
            }
        } catch (error) {
            showNotification(error.message, 'error');
        }
    });

    // Password confirmation validation
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('register-password').value;
        if (this.value !== password) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });

    // Show notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification--${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    // Handle tab change from URL hash
    const hash = window.location.hash;
    if (hash === '#register') {
        const registerTab = new bootstrap.Tab(document.getElementById('register-tab'));
        registerTab.show();
    }
});
</script>

<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 4px;
    color: #fff;
    z-index: 1050;
    animation: slideIn 0.3s ease-out;
}

.notification--success {
    background-color: #28a745;
}

.notification--error {
    background-color: #dc3545;
}

.notification--info {
    background-color: #17a2b8;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.password-toggle {
    cursor: pointer;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.card {
    border: none;
}

.card-header {
    background-color: transparent;
    border-bottom: none;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 1rem 1.5rem;
    font-weight: 500;
    transition: color 0.15s ease-in-out;
}

.nav-tabs .nav-link:hover {
    color: #495057;
    border: none;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: transparent;
    border-bottom: 2px solid #007bff;
}

.btn-primary {
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.input-group-text {
    background-color: transparent;
    cursor: pointer;
}

.toggle-password:focus {
    box-shadow: none;
}
</style> 