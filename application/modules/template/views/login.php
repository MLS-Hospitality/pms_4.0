<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?php echo html_escape((!empty($setting->title) ? $setting->title : null)) ?> ::
        <?php echo html_escape((!empty($title) ? $title : null)) ?></title>
    <!-- Favicon and touch icons -->
    <link rel="shortcut icon"
        href="<?php echo base_url((!empty($setting->favicon) ? $setting->favicon : 'assets/img/fav.png')) ?>"
        type="image/x-icon">
    <!-- Font Awesome for icons -->
    <link href="<?php echo base_url('assets/plugins/fontawesome/css/all.min.css') ?>" rel="stylesheet"
        type="text/css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --primary: #d4a574;
            --primary-dark: #b88450;
            --primary-light: #e8d4b8;
            --accent: #2c3e50;
            --accent-light: #34495e;
            --text-primary: #1a1a1a;
            --text-secondary: #6c757d;
            --text-light: #9ca3af;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-overlay: rgba(255, 255, 255, 0.95);
            --border: #e5e7eb;
            --border-focus: #d4a574;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
            --shadow-xl: 0 20px 60px rgba(0, 0, 0, 0.15);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s ease;
        }

        html {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            min-height: -webkit-fill-available;
            background: #F9F6FD;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(212, 165, 116, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(184, 132, 80, 0.15) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            display: flex;
            width: 100%;
            min-height: 100vh;
            min-height: -webkit-fill-available;
            position: relative;
            z-index: 1;
        }

        .login-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(30px, 5vw, 60px) clamp(20px, 4vw, 40px);
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .login-box {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 2;
            animation: slideInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-card {
            background: var(--bg-overlay);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: var(--radius-xl);
            padding: clamp(35px, 5vw, 50px);
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark), var(--primary));
            background-size: 200% 100%;
            animation: shimmer 3s ease infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .logo-container {
            text-align: center;
            margin-bottom: clamp(30px, 4vw, 40px);
            animation: fadeIn 1s ease-out 0.2s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .logo-container img {
            max-width: clamp(140px, 30vw, 200px);
            width: 100%;
            height: auto;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.1));
            transition: transform 0.3s ease;
        }

        .logo-container img:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .welcome-section {
            text-align: center;
            margin-bottom: clamp(30px, 4vw, 40px);
            animation: fadeIn 1s ease-out 0.3s both;
        }

        h1 {
            font-size: clamp(1.875rem, 5vw, 2.5rem);
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: clamp(0.9375rem, 2.5vw, 1.0625rem);
            font-weight: 400;
            line-height: 1.6;
        }

        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            font-size: 0.9rem;
            animation: slideDown 0.4s ease-out;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.1) 0%, rgba(25, 118, 210, 0.1) 100%);
            color: #1565c0;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(198, 40, 40, 0.1) 100%);
            color: #c62828;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .alert-dismissable {
            position: relative;
            padding-right: 45px;
        }

        .alert .close {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
            transition: var(--transition);
            min-width: 36px;
            min-height: 36px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            touch-action: manipulation;
        }

        .alert .close:hover,
        .alert .close:active {
            opacity: 1;
            background: rgba(0, 0, 0, 0.08);
            transform: translateY(-50%) rotate(90deg);
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
            animation: fadeIn 1s ease-out 0.4s both;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.5s;
        }

        label {
            display: block;
            color: var(--text-primary);
            font-size: 0.9375rem;
            font-weight: 600;
            margin-bottom: 10px;
            letter-spacing: 0.2px;
            transition: var(--transition);
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
            transition: var(--transition);
            z-index: 1;
        }

        .input-wrapper input:focus ~ .input-icon {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 50px;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 8px;
            transition: var(--transition);
            border-radius: 50%;
            min-width: 40px;
            min-height: 40px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        .password-toggle:hover,
        .password-toggle:active {
            color: var(--primary);
            background: rgba(212, 165, 116, 0.1);
            transform: translateY(-50%) scale(1.1);
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 16px;
            transition: var(--transition);
            background-color: rgba(255, 255, 255, 0.8);
            color: var(--text-primary);
            box-shadow: var(--shadow-sm);
            -webkit-appearance: none;
            appearance: none;
            touch-action: manipulation;
            font-weight: 400;
        }

        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: var(--border-focus);
            background-color: var(--bg-primary);
            box-shadow: 0 0 0 4px rgba(212, 165, 116, 0.1), var(--shadow-md);
            transform: translateY(-2px);
        }

        input::placeholder {
            color: var(--text-light);
            font-weight: 400;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 28px 0 32px 0;
            animation: fadeIn 1s ease-out 0.6s both;
        }

        .forgot-password {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            padding: 8px 4px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            touch-action: manipulation;
        }

        .forgot-password::after {
            content: '';
            position: absolute;
            bottom: 6px;
            left: 4px;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            transition: width 0.3s ease;
        }

        .forgot-password:hover,
        .forgot-password:active {
            color: var(--primary);
        }

        .forgot-password:hover::after {
            width: calc(100% - 8px);
        }

        .login-btn {
            width: 100%;
            padding: 18px 24px;
            min-height: 56px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(212, 165, 116, 0.4);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            animation: fadeIn 1s ease-out 0.7s both;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #a6753f 100%);
            box-shadow: 0 8px 25px rgba(212, 165, 116, 0.5);
            transform: translateY(-2px);
        }

        .login-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(212, 165, 116, 0.4);
        }

        .image-section {
            flex: 1;
            background-image: url('<?php echo base_url('assets/img/login_logo.jpeg') ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            min-height: 300px;
        }

        .image-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.5) 0%, rgba(52, 73, 94, 0.4) 100%);
            z-index: 1;
        }

        .image-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            z-index: 2;
            opacity: 0.6;
        }

        /* Accessibility - Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }

            body {
                animation: none;
            }
        }

        /* Tablet Landscape and Below */
        @media (max-width: 1024px) and (orientation: landscape) {
            .container {
                min-height: auto;
            }

            .login-section {
                padding: clamp(20px, 3vw, 30px) clamp(20px, 3vw, 30px);
            }

            .image-section {
                display: none;
            }

            .login-box {
                max-width: 500px;
            }
        }

        /* Tablet Portrait and Below */
        @media (max-width: 968px) {
            body {
                background: #F9F6FD;
            }

            .container {
                flex-direction: column;
            }

            .login-section {
                padding: clamp(30px, 4vw, 40px) clamp(20px, 4vw, 30px);
                min-height: 100vh;
                min-height: -webkit-fill-available;
            }

            .login-card {
                padding: clamp(30px, 4vw, 40px);
            }

            .image-section {
                min-height: clamp(200px, 30vh, 300px);
                order: -1;
                flex: none;
            }
        }

        /* Mobile Landscape */
        @media (max-width: 768px) and (orientation: landscape) {
            .image-section {
                display: none;
            }

            .login-section {
                padding: 20px;
            }

            .login-card {
                padding: 25px;
            }

            .logo-container {
                margin-bottom: 20px;
            }

            .welcome-section {
                margin-bottom: 25px;
            }

            .form-group {
                margin-bottom: 18px;
            }
        }

        /* Mobile Portrait */
        @media (max-width: 480px) {
            .login-section {
                padding: clamp(25px, 5vh, 35px) 20px;
            }

            .login-card {
                padding: clamp(25px, 4vw, 30px);
                border-radius: var(--radius-lg);
            }

            .logo-container {
                margin-bottom: 25px;
            }

            .welcome-section {
                margin-bottom: 30px;
            }

            .options {
                margin: 24px 0 28px 0;
            }

            input[type="email"],
            input[type="password"],
            input[type="text"] {
                padding: 14px 14px 14px 44px;
            }

            .password-wrapper input {
                padding-right: 50px;
            }
        }

        /* Extra Small Devices */
        @media (max-width: 360px) {
            .login-section {
                padding: 20px 16px;
            }

            .login-card {
                padding: 20px;
            }

            .logo-container {
                margin-bottom: 20px;
            }

            .welcome-section {
                margin-bottom: 25px;
            }

            .alert {
                padding: 12px 40px 12px 14px;
                font-size: 0.875rem;
            }

            .form-group {
                margin-bottom: 18px;
            }
        }

        /* High DPI Screens */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            body {
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
        }

        /* iOS Safe Area Support */
        @supports (padding: max(0px)) {
            .login-section {
                padding-left: max(20px, env(safe-area-inset-left));
                padding-right: max(20px, env(safe-area-inset-right));
                padding-bottom: max(30px, env(safe-area-inset-bottom));
            }
        }

        /* Touch Device Optimization */
        @media (hover: none) and (pointer: coarse) {
            .login-btn:hover {
                transform: none;
            }

            .logo-container img:hover {
                transform: none;
            }

            .password-toggle:hover,
            .forgot-password:hover {
                transform: none;
            }

            input[type="email"]:focus,
            input[type="password"]:focus,
            input[type="text"]:focus {
                transform: none;
            }
        }

        /* Loading state for form submission */
        .login-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .login-btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-section">
            <div class="login-box">
                <div class="login-card">
                    <div class="logo-container">
                        <img src="<?php echo html_escape(base_url((!empty($web_setting->login_logo) ? $web_setting->login_logo : 'assets/img/login_logo.png'))) ?>"
                            alt="Logo">
                    </div>

                    <div class="welcome-section">
                        <h1><?php echo display('sign_in') ?></h1>
                        <p class="subtitle"><?php echo display('sign_in_using_your_email_address') ?></p>
                    </div>

                    <!-- alert message -->
                    <?php if ($this->session->flashdata('message') != null) {  ?>
                    <div class="alert alert-info alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <?php echo $this->session->flashdata('message'); ?>
                    </div>
                    <?php } ?>
                    <?php if ($this->session->flashdata('exception') != null) {  ?>
                    <div class="alert alert-danger alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <?php echo $this->session->flashdata('exception'); ?>
                    </div>
                    <?php } ?>
                    <?php if (validation_errors()) {  ?>
                    <div class="alert alert-danger alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <?php echo validation_errors(); ?>
                    </div>
                    <?php } ?>

                    <?php echo form_open('login', 'id="loginForm" novalidate'); ?>

                    <div class="form-group">
                        <label for="inputEmail"><?php echo display("your_email") ?></label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" autocomplete="off" id="inputEmail"
                                placeholder="<?php echo display('email') ?>" required autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputPassword"><?php echo display('password') ?></label>
                        <div class="input-wrapper password-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="password"
                                placeholder="<?php echo display('password') ?>" name="password"
                                id="inputPassword" required>
                            <i onclick="passShow()" class="fa fa-eye-slash password-toggle" id="passwordToggle"></i>
                        </div>
                    </div>

                    <div class="options">
                        <a href="forgot-password" class="forgot-password"><?php echo display('forgot_password') ?></a>
                    </div>

                    <button type="submit" class="login-btn">
                        <span><?php echo display('login') ?></span>
                    </button>

                    <?php echo form_close() ?>
                </div>
            </div>
        </div>
        <div class="image-section"></div>
    </div>

    <!-- jQuery -->
    <script src="<?php echo base_url(); ?>assets/plugins/jQuery/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="<?php echo base_url(); ?>assets/plugins/bootstrap/js/popper.min.js"></script>
    <script src="<?php echo base_url('assets/plugins/bootstrap/js/bootstrap.min.js') ?>" type="text/javascript"></script>
    <script src="<?php echo base_url('assets/js/login.js') ?>" type="text/javascript"></script>
    <script src="<?php echo base_url('assets/js/password.js') ?>" type="text/javascript"></script>

    <script>
        // Enhanced form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            var submitBtn = this.querySelector('.login-btn');
            if (submitBtn && !submitBtn.classList.contains('loading')) {
                submitBtn.classList.add('loading');
                var btnText = submitBtn.querySelector('span');
                if (btnText) {
                    btnText.style.opacity = '0';
                }
            }
        });

        // Enhanced alert dismissal with animation
        document.addEventListener('DOMContentLoaded', function() {
            var alerts = document.querySelectorAll('.alert-dismissable .close');
            alerts.forEach(function(closeBtn) {
                closeBtn.addEventListener('click', function() {
                    var alert = this.closest('.alert');
                    if (alert) {
                        alert.style.transition = 'all 0.3s ease';
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateY(-10px)';
                        setTimeout(function() {
                            alert.remove();
                        }, 300);
                    }
                });
            });
        });
    </script>

</body>

</html>
