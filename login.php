<?php
session_start();
include 'db_connect.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!$username || !$password) {
        $errors[] = "Username and password required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // admin or user
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Login - Matrimony</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
  body { 
    background: linear-gradient(135deg, #ff758c, #ff7eb3);
    height: 100vh; display: flex; justify-content: center; align-items: center; 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
  }
  .login-container {
    background: white;
    border-radius: 25px;
    padding: 50px 40px;
    box-shadow: 0 20px 60px rgba(255, 118, 140, 0.4);
    width: 400px;
  }
  h2 {
    text-align: center;
    margin-bottom: 35px;
    font-weight: 800;
    color: #d6336c;
    letter-spacing: 1.5px;
  }
  .form-control {
    border-radius: 15px;
    padding: 14px 20px;
    font-size: 16px;
    border: 2px solid #eee;
    transition: 0.3s;
  }
  .form-control:focus {
    border-color: #d6336c;
    box-shadow: 0 0 8px rgba(214, 51, 108, 0.4);
  }
  .btn-login {
    background: #d6336c;
    border: none;
    border-radius: 20px;
    padding: 14px 0;
    font-weight: 700;
    font-size: 18px;
    width: 100%;
    color: white;
    box-shadow: 0 6px 20px rgba(214, 51, 108, 0.5);
    transition: 0.3s;
  }
  .btn-login:hover {
    background: #a62852;
  }
  .errors {
    margin-bottom: 20px;
  }
  .errors ul {
    list-style: none;
    padding-left: 0;
    margin: 0;
  }
  .errors li {
    color: #b02a37;
    font-weight: 600;
    margin-bottom: 8px;
  }
  .form-label {
    font-weight: 700;
    color: #d6336c;
  }
  .invalid-feedback {
    font-size: 0.9em;
  }
  .signup-text {
    text-align: center;
    margin-top: 20px;
  }
  .signup-text a {
    color: #d6336c;
    font-weight: 700;
    text-decoration: none;
  }
  .signup-text a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>
<div class="login-container">
    <h2><i class="fas fa-heart"></i> Matrimony Login</h2>

    <?php if ($errors): ?>
        <div class="errors alert alert-danger" role="alert">
            <ul>
            <?php foreach ($errors as $error): ?>
                <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" id="loginForm" novalidate>
        <div class="mb-4">
            <label for="username" class="form-label">Username</label>
            <input type="text" id="username" name="username" class="form-control" required autocomplete="username" autofocus>
            <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Please enter your username.</div>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
            <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Please enter your password.</div>
        </div>
        <button type="submit" class="btn btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
    </form>
    <p class="signup-text">Don't have an account? <a href="signup.php">Sign up here</a>.</p>
</div>

<script>
document.getElementById('loginForm').addEventListener('input', function(e) {
    const input = e.target;
    if (input.checkValidity()) {
        input.classList.remove('is-invalid');
    } else {
        input.classList.add('is-invalid');
    }
});
document.getElementById('loginForm').addEventListener('submit', function(e) {
    let valid = true;
    const form = e.target;
    form.querySelectorAll('[required]').forEach(input => {
        input.classList.remove('is-invalid');
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        }
    });
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
