<?php
session_start();
include('db_connect.php');

ob_start();
$system = $conn->query("SELECT * FROM system_settings")->fetch_array();

foreach ($system as $k => $v) {
    $_SESSION['system'][$k] = $v;
}
ob_end_flush();

if (isset($_SESSION['login_id'])) {
    header("location:index.php?page=home");
    exit;
}
?>

<?php include 'header.php'; ?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    html, body {
        min-height: 100%;
    }

    body.login-page {
        min-height: 100vh;
        background: radial-gradient(circle at top left, rgba(79, 125, 227, 0.18) 0%, transparent 32%),
                    radial-gradient(circle at bottom right, rgba(29, 91, 66, 0.16) 0%, transparent 28%),
                    linear-gradient(180deg, #eaf4ff 0%, #f9fbff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px;
        overflow-x: hidden;
    }

    .portal-container {
        width: 100%;
        max-width: 1120px;
        min-height: 680px;
        background: rgba(255, 255, 255, 0.96);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 28px 80px rgba(17, 56, 97, 0.14);
        display: flex;
        transition: transform 0.35s ease;
    }

    .portal-container:hover {
        transform: translateY(-4px);
    }

    .left-panel {
        width: 45%;
        background: linear-gradient(135deg, #1d5b42 0%, #256b61 100%);
        color: #fff;
        padding: 50px 42px;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        overflow: hidden;
    }

    .title-block {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 0;
    }

    .left-panel::before,
    .left-panel::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        filter: blur(2px);
    }

    .left-panel::before {
        width: 260px;
        height: 260px;
        top: -90px;
        right: -90px;
    }

    .left-panel::after {
        width: 200px;
        height: 200px;
        bottom: -90px;
        left: -50px;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 64px;
        position: relative;
        z-index: 1;
    }

    .brand img {
        width: 70px;
        height: 70px;
        border-radius: 18px;
        padding: 12px;
        object-fit: contain;
    }

    .brand-text h2 {
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .brand-text p {
        font-size: 13px;
        opacity: 0.85;
        margin: 0;
        line-height: 1.4;
    }

    .system-title {
        color: #f7da55;
        font-size: 44px;
        font-weight: 800;
        line-height: 1.05;
        margin-bottom: 18px;
        position: relative;
        z-index: 1;
    }

    .system-desc {
        font-size: 16px;
        line-height: 1.9;
        color: rgba(255, 255, 255, 0.92);
        max-width: 420px;
        margin-bottom: 32px;
        position: relative;
        z-index: 1;
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .feature-list li {
        padding: 16px 0;
        font-size: 16px;
        color: rgba(255, 255, 255, 0.93);
        position: relative;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    .feature-list li:last-child {
        border-bottom: none;
    }

    .feature-list i {
        color: #f7da55;
        margin-right: 12px;
        width: 22px;
        text-align: center;
    }

    .contact-info {
        position: relative;
        z-index: 1;
        margin-top: 28px;
    }

    .contact-info p {
        margin-bottom: 12px;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.88);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .contact-info i {
        color: #f7da55;
        min-width: 20px;
        text-align: center;
    }

    .right-panel {
        width: 55%;
        background: #ffffff;
        padding: 60px 55px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .portal-title {
        text-align: center;
        color: #102a43;
        font-size: 34px;
        font-weight: 800;
        margin-bottom: 10px;
    }

    .title-line {
        width: 72px;
        height: 5px;
        background: #1d5b42;
        margin: 0 auto 30px;
        border-radius: 999px;
    }

    .portal-subtitle {
        text-align: center;
        color: #5f6f83;
        font-size: 16px;
        margin-bottom: 30px;
    }

    .access-btn {
        border: none;
        width: 100%;
        padding: 18px 22px;
        color: #fff;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        transition: transform 0.25s ease, box-shadow 0.25s ease, opacity 0.25s ease;
        box-shadow: 0 18px 35px rgba(17, 56, 97, 0.08);
    }

    .access-btn:hover {
        transform: translateY(-2px);
        opacity: 0.98;
        box-shadow: 0 22px 42px rgba(17, 56, 97, 0.12);
    }

    .admin-btn {
        background: linear-gradient(135deg, #1d5b42 0%, #2f7b64 100%);
    }

    .coord-btn {
        background: linear-gradient(135deg, #d16258 0%, #e27b6e 100%);
    }

    .eval-btn {
        background: linear-gradient(135deg, #4f7de3 0%, #6ca0f0 100%);
    }

    #login-card {
        display: none;
        margin-top: 32px;
    }

    #login-card .card-body {
        padding: 30px 28px;
    }

    .login-panel {
        background: #f7fbff;
        border-radius: 24px;
        border: 1px solid rgba(40, 84, 128, 0.08);
        box-shadow: 0 24px 70px rgba(17, 56, 97, 0.08);
        padding: 26px;
    }

    .login-panel h3 {
        font-size: 22px;
        font-weight: 700;
        color: #102a43;
        margin-bottom: 20px;
        text-align: center;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-control {
        border: 1px solid #d7e1ec;
        border-radius: 14px;
        padding: 16px 18px;
        font-size: 15px;
        transition: border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .form-control:focus {
        border-color: #1d5b42;
        box-shadow: 0 0 0 0.2rem rgba(29, 91, 66, 0.12);
    }

    .password-wrapper {
        position: relative;
    }

    .password-wrapper .form-control {
        padding-right: 52px;
    }

    /* Hide Edge's built-in reveal button so only one eye shows */
    .password-wrapper input::-ms-reveal {
        display: none;
    }

    .toggle-password {
        position: absolute;
        top: 50%;
        right: 14px;
        transform: translateY(-50%);
        background: none;
        border: none;
        padding: 6px;
        color: #7b8a9c;
        cursor: pointer;
        transition: color 0.25s ease;
    }

    .toggle-password:hover,
    .toggle-password:focus {
        color: #1d5b42;
        outline: none;
    }

    .btn-success {
        background: #1d5b42 !important;
        border: none !important;
        padding: 16px 18px !important;
        border-radius: 14px !important;
        font-size: 16px !important;
        font-weight: 700 !important;
        transition: transform 0.25s ease, filter 0.25s ease !important;
    }

    .btn-success:hover {
        transform: translateY(-1px) !important;
        filter: brightness(1.05) !important;
    }

    .notice-box {
        margin-top: 28px;
        background: #eff7ff;
        border-left: 5px solid #4f7de3;
        border-radius: 18px;
        padding: 18px 22px;
        color: #344a6e;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        box-shadow: 0 18px 35px rgba(79, 125, 227, 0.12);
    }

    .notice-box i {
        min-width: 18px;
        color: #4f7de3;
    }

    @media (max-width: 991px) {
        body.login-page {
            padding: 18px;
        }

        .left-panel {
            padding: 38px 28px;
        }

        .right-panel {
            padding: 38px 32px 42px;
        }

        .portal-title {
            font-size: 30px;
        }

        .system-title {
            font-size: 36px;
        }
    }

    @media (max-width: 767px) {
        .portal-container {
            flex-direction: column;
            min-height: auto;
        }

        .left-panel,
        .right-panel {
            width: 100%;
        }

        .right-panel {
            padding: 38px 28px 42px;
        }
    }

    @media (max-width: 640px) {
        .portal-container {
            border-radius: 22px;
        }

        .left-panel,
        .right-panel {
            padding-left: 20px;
            padding-right: 20px;
        }

        .brand {
            flex-direction: column;
            align-items: flex-start;
        }

        .left-panel::before,
        .left-panel::after {
            display: none;
        }

        .access-btn {
            font-size: 15px;
        }

        .portal-title {
            font-size: 28px;
        }
    }
</style>

<body class="hold-transition login-page">

<div class="portal-container">

    <div class="left-panel">

        <div class="brand">
            <img src="assets/uploads/logo.webp" alt="ISU Logo">

            <div class="brand-text">
                <h2>ISABELA STATE UNIVERSITY</h2>
                <p>ILAGAN CAMPUS - EXTENSION OFFICE</p>
            </div>
        </div>

        <h1 class="system-title">ExtenTrack Analytics</h1>

    </div>

    <div class="right-panel">

        <h1 class="portal-title">ACCESS PORTAL</h1>

        <div class="title-line"></div>

        <p class="portal-subtitle">
            Select your access type:
        </p>

        <button class="access-btn admin-btn" data-role="1">
            <i class="fas fa-user-shield mr-2"></i>
            ADMINISTRATOR ACCESS
        </button>

        <button class="access-btn coord-btn" data-role="2">
            <i class="fas fa-users mr-2"></i>
            EXTENSION COORDINATOR ACCESS
        </button>

      
        <div id="login-card">
            <div class="login-panel">
                <h3>Sign in to your portal</h3>
                <form id="login-form">
                    <input type="hidden" name="login" id="login-role">
                    <div class="form-group mt-4">
                        <input
                            type="email"
                            class="form-control form-control-lg"
                            name="email"
                            placeholder="Email Address"
                            required>
                    </div>
                    <div class="form-group password-wrapper">
                        <input
                            type="password"
                            class="form-control form-control-lg"
                            name="password"
                            id="password"
                            placeholder="Password"
                            required>
                        <button
                            type="button"
                            class="toggle-password"
                            id="toggle-password"
                            aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg btn-block">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>
            </div>
        </div>

        <div class="notice-box">
            <i class="fas fa-lock"></i>
            Unauthorized access is prohibited. All activities are logged.
        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const accessButtons = document.querySelectorAll('.access-btn');
    const loginCard = document.getElementById('login-card');
    const loginRole = document.getElementById('login-role');
    const loginForm = document.getElementById('login-form');

    /* =========================
       ACCESS TYPE TOGGLE
    ========================= */
    accessButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const role = this.getAttribute('data-role');

            // Set selected role
            loginRole.value = role;

            // Show login card
            loginCard.style.display = 'block';

            // Smooth animation
            loginCard.style.opacity = '0';
            loginCard.style.transform = 'translateY(15px)';

            setTimeout(function () {
                loginCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                loginCard.style.opacity = '1';
                loginCard.style.transform = 'translateY(0)';
            }, 10);

            // Scroll to login form
            setTimeout(function () {
                loginCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 100);

        });

    });


    /* =========================
       SHOW / HIDE PASSWORD
    ========================= */
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('toggle-password');

    togglePassword.addEventListener('click', function () {

        const isHidden = passwordInput.type === 'password';

        passwordInput.type = isHidden ? 'text' : 'password';

        this.querySelector('i').className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');

    });


    /* =========================
       LOGIN FORM
    ========================= */
    loginForm.addEventListener('submit', function (e) {

        e.preventDefault();

        const form = this;
        const submitButton = form.querySelector('button[type="submit"]');

        // Remove previous error
        const oldAlert = form.querySelector('.alert-danger');

        if (oldAlert) {
            oldAlert.remove();
        }

        // Disable button
        submitButton.disabled = true;
        submitButton.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Signing in...';

        const formData = new FormData(form);

        fetch('ajax.php?action=login', {
            method: 'POST',
            body: formData
        })

        .then(function (response) {
            return response.text();
        })

        .then(function (resp) {

            resp = resp.trim();

            console.log('Login response:', resp);

            if (resp === '1') {

                window.location.href = 'index.php?page=home';

            } else {

                const alert = document.createElement('div');

                alert.className = 'alert alert-danger';
                alert.style.borderRadius = '12px';
                alert.style.marginBottom = '18px';

                alert.innerHTML =
                    '<i class="fas fa-exclamation-circle mr-2"></i>' +
                    'Email or password is incorrect.';

                form.prepend(alert);

                submitButton.disabled = false;

                submitButton.innerHTML =
                    '<i class="fas fa-sign-in-alt"></i> Sign In';
            }

        })

        .catch(function (error) {

            console.error('Login error:', error);

            const alert = document.createElement('div');

            alert.className = 'alert alert-danger';
            alert.style.borderRadius = '12px';
            alert.style.marginBottom = '18px';

            alert.innerHTML =
                '<i class="fas fa-exclamation-triangle mr-2"></i>' +
                'Unable to connect to the server. Please try again.';

            form.prepend(alert);

            submitButton.disabled = false;

            submitButton.innerHTML =
                '<i class="fas fa-sign-in-alt"></i> Sign In';
        });

    });

});
</script>

<?php include 'footer.php'; ?>
