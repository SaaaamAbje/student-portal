<?php
session_start();

// If already logged in, redirect to the appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            exit();
        case 'teacher':
            header("Location: teacher/dashboard.php");
            exit();
        case 'student':
            header("Location: student/dashboard.php");
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        // Apply theme before paint to prevent flash
        (function(){var t=localStorage.getItem('sp-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();
    </script>
</head>
<body>
    <div class="landing-hero">
        <h1>Student Portal</h1>
        <p>View grades, manage enrollments, and track academic performance — all in one place.</p>
        <div class="landing-actions">
            <a href="auth/login.php" class="btn btn-primary">Login</a>
            <a href="auth/register.php" class="btn btn-secondary">Register as Student</a>
        </div>
    </div>

    <div class="landing-features">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Track Grades</h3>
            <p>Students can view midterm, finals, and final grades for every enrolled subject.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🧮</div>
            <h3>Automatic GPA</h3>
            <p>GPA is calculated automatically using unit-weighted averages of final grades.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🗂️</div>
            <h3>Admin Tools</h3>
            <p>Admins manage students, teachers, subjects, enrollments, and grade entries.</p>
        </div>
    </div>

    <div class="landing-footer">
        &copy; <?php echo date('Y'); ?> Student Portal. Built with PHP &amp; MySQL.
    </div>
</body>
</html>