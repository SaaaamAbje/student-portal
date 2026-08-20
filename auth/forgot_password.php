<?php
/**
 * Forgot Password
 * In production this would send a reset email. Since this runs on XAMPP
 * (no mail server), we verify identity by matching username + email, then
 * let the user set a new password directly — a common pattern for internal
 * school systems with no external mail server configured.
 */

session_start();
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$step = 1; // Step 1: verify identity | Step 2: set new password
$error = '';
$success = '';
$verified_user_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ─── Step 1: Verify username + email ───────────────────────────────────
    if (isset($_POST['step']) && $_POST['step'] === '1') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');

        if (empty($username) || empty($email)) {
            $error = 'Please enter both your username and registered email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $_SESSION['reset_user_id'] = $row['user_id'];
                $step = 2;
            } else {
                $error = 'No account found with that username and email combination.';
            }
            $stmt->close();
        }
    }

    // ─── Step 2: Set new password ──────────────────────────────────────────
    elseif (isset($_POST['step']) && $_POST['step'] === '2') {
        $user_id         = (int)($_SESSION['reset_user_id'] ?? 0);
        $new_password    = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($user_id <= 0) {
            $error = 'Session expired. Please start again.';
            $step = 1;
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
            $step = 2;
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
            $step = 2;
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed, $user_id);

            if ($stmt->execute()) {
                unset($_SESSION['reset_user_id']);
                $success = 'Password reset successfully! You can now log in with your new password.';
                $step = 1;
            } else {
                $error = 'Failed to reset password. Please try again.';
                $step = 2;
            }
            $stmt->close();
        }
    }
} else {
    // Check if already in step 2 (user came back via browser back button)
    if (isset($_SESSION['reset_user_id'])) {
        $step = 2;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        // Apply theme before paint to prevent flash
        (function(){var t=localStorage.getItem('sp-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();
    </script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Reset Password</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <a href="login.php">Go to Login</a>
                </div>
            <?php endif; ?>

            <?php if (empty($success)): ?>

                <?php if ($step === 1): ?>
                    <!-- Step 1: Identify Account -->
                    <p style="color:var(--color-text-muted);font-size:13px;text-align:center;margin-bottom:20px;">
                        Enter your username and registered email to verify your identity.
                    </p>
                    <form action="forgot_password.php" method="POST">
                        <input type="hidden" name="step" value="1">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="email">Registered Email</label>
                            <input type="email" id="email" name="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Verify Identity</button>
                    </form>

                <?php elseif ($step === 2): ?>
                    <!-- Step 2: New Password -->
                    <p style="color:var(--color-text-muted);font-size:13px;text-align:center;margin-bottom:20px;">
                        Identity verified. Enter your new password below.
                    </p>
                    <form action="forgot_password.php" method="POST">
                        <input type="hidden" name="step" value="2">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6" autofocus>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </form>
                    <p class="auth-link">
                        <a href="forgot_password.php" onclick="<?php echo "fetch('forgot_password.php?clear=1');"; ?>">
                            &larr; Start over
                        </a>
                    </p>
                <?php endif; ?>

            <?php endif; ?>

            <p class="auth-link"><a href="login.php">&larr; Back to Login</a></p>
        </div>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>