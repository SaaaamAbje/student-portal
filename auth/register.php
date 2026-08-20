<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name       = trim($_POST['full_name'] ?? '');
    $student_number  = trim($_POST['student_number'] ?? '');
    $course          = trim($_POST['course'] ?? '');
    $year_level      = trim($_POST['year_level'] ?? '');
    $section         = trim($_POST['section'] ?? '');

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name) || empty($student_number)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Username or email already exists.';
            $stmt->close();
        } else {
            $stmt->close();

            // Check if student number already exists
            $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_number = ?");
            $stmt->bind_param("s", $student_number);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = 'Student number already exists.';
                $stmt->close();
            } else {
                $stmt->close();

                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'student';

                // Start transaction to ensure both inserts succeed
                $conn->begin_transaction();

                try {
                    // Insert into users table
                    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);
                    $stmt->execute();
                    $user_id = $conn->insert_id;
                    $stmt->close();

                    // Insert into students table
                    $stmt = $conn->prepare("INSERT INTO students (user_id, student_number, full_name, course, year_level, section) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssss", $user_id, $student_number, $full_name, $course, $year_level, $section);
                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();
                    $success = 'Registration successful! You can now log in.';
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        // Apply theme before paint to prevent flash
        (function(){var t=localStorage.getItem('sp-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();
    </script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Student Registration</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <a href="login.php">Go to Login</a>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="student_number">Student Number *</label>
                    <input type="text" id="student_number" name="student_number" value="<?php echo htmlspecialchars($_POST['student_number'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="course">Course</label>
                    <input type="text" id="course" name="course" value="<?php echo htmlspecialchars($_POST['course'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <input type="text" id="year_level" name="year_level" value="<?php echo htmlspecialchars($_POST['year_level'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="section">Section</label>
                    <input type="text" id="section" name="section" value="<?php echo htmlspecialchars($_POST['section'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn btn-primary">Register</button>
            </form>

            <p class="auth-link">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>