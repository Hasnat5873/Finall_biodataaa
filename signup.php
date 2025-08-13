<?php
session_start();
include 'db_connect.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user'; // default role user if not set

    // Basic validations
    if (!$username || !$email || !$password || !$confirm) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } elseif (!in_array($role, ['admin', 'user'])) {
        $errors[] = "Invalid role selected.";
    } else {
        try {
            // Check username or email already taken
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = "Username or Email already taken.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hash, $role])) {
                    header("Location: login.php");
                    exit;
                } else {
                    $errors[] = "Signup failed. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Signup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: linear-gradient(135deg, #6e48aa, #9d50bb); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
  .container { max-width: 450px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
  h2 { color: #2c3e50; margin-bottom: 30px; font-weight: bold; text-align: center; }
  .form-control { border-radius: 10px; padding: 12px; transition: border-color 0.3s; }
  .btn-primary { background: #007bff; border: none; border-radius: 10px; padding: 12px; font-size: 18px; }
  .btn-primary:hover { background: #0056b3; }
  .errors { color: #dc3545; margin-bottom: 20px; }
  .is-invalid { border-color: #dc3545 !important; }
  .invalid-feedback { display: none; color: #dc3545; font-size: 0.9em; }
  .form-label { font-weight: bold; color: #2c3e50; }
  .form-control:focus { box-shadow: 0 0 5px rgba(0,123,255,0.5); }
</style>
</head>
<body>
<div class="container">
<h2><i class="fas fa-user-plus"></i> Create Account</h2>
<?php if ($errors): ?>
    <div class="errors alert alert-danger">
        <ul>
        <?php foreach ($errors as $error): ?>
            <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form method="POST" id="signupForm" novalidate>
    <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Username is required.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Valid email is required.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required minlength="6">
        <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Password must be at least 6 characters.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required>
        <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Passwords must match.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-control" required>
            <option value="user" <?= (($_POST['role'] ?? '') === 'user') ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
        </select>
        <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Role selection is required.</div>
    </div>
    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-user-plus"></i> Signup</button>
</form>
<p class="text-center mt-3">Already have an account? <a href="login.php" class="text-primary">Login here</a>.</p>
</div>
<script>
document.getElementById('signupForm').addEventListener('input', function(e) {
    const input = e.target;
    if (input.name === 'confirm_password') {
        const pwd = document.querySelector('[name="password"]').value;
        if (input.value !== pwd) {
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    } else if (input.checkValidity()) {
        input.classList.remove('is-invalid');
    } else {
        input.classList.add('is-invalid');
    }
});
document.getElementById('signupForm').addEventListener('submit', function(e) {
    let valid = true;
    const form = e.target;
    form.querySelectorAll('[required]').forEach(input => {
        input.classList.remove('is-invalid');
        if (!input.checkValidity() || (input.name === 'confirm_password' && input.value !== form.querySelector('[name="password"]').value)) {
            input.classList.add('is-invalid');
            valid = false;
        }
    });
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
