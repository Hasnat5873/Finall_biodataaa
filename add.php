<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullName']);
    $fatherName = trim($_POST['fatherName']);
    $motherName = trim($_POST['motherName']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $maritalStatus = $_POST['maritalStatus'];
    $religion = $_POST['religion'];
    $height = $_POST['height'];
    $occupation = trim($_POST['occupation']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $about = trim($_POST['about']);
    $hobby = isset($_POST['hobby']) ? implode(', ', $_POST['hobby']) : '';
    $education = $_POST['education'] ?? [];

    $profilePicture = '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024;
        $file = $_FILES['profile_picture'];

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $profilePicture = $uploadPath;
            } else {
                $errors[] = "Failed to upload profile picture. Check directory permissions.";
            }
        } else {
            $errors[] = "Invalid file type or size. Allowed: JPEG, PNG, GIF, max 2MB.";
        }
    }

    if (!$fullName || !$dob || !$gender || !$occupation || !$email || !$phone) {
        $errors[] = "Required fields missing.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (!preg_match('/^\d{10,15}$/', $phone)) {
        $errors[] = "Phone must be 10-15 digits.";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO biodata (user_id, fullName, fatherName, motherName, dob, gender, maritalStatus, religion, height, occupation, email, phone, about, hobby, profile_picture, created_at, updated_at) VALUES (:user_id, :fullName, :fatherName, :motherName, :dob, :gender, :maritalStatus, :religion, :height, :occupation, :email, :phone, :about, :hobby, :profile_picture, NOW(), NOW())");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':fullName' => $fullName,
                ':fatherName' => $fatherName,
                ':motherName' => $motherName,
                ':dob' => $dob,
                ':gender' => $gender,
                ':maritalStatus' => $maritalStatus,
                ':religion' => $religion,
                ':height' => $height,
                ':occupation' => $occupation,
                ':email' => $email,
                ':phone' => $phone,
                ':about' => $about,
                ':hobby' => $hobby,
                ':profile_picture' => $profilePicture
            ]);
            $biodata_id = $conn->lastInsertId();

            $eduStmt = $conn->prepare("INSERT INTO educational_qualification (biodata_id, degree, institution, year, result) VALUES (:biodata_id, :degree, :institution, :year, :result)");
            foreach ($education as $edu) {
                if (trim($edu['degree']) !== '' && trim($edu['institution']) !== '') {
                    $eduStmt->execute([
                        ':biodata_id' => $biodata_id,
                        ':degree' => trim($edu['degree']),
                        ':institution' => trim($edu['institution']),
                        ':year' => trim($edu['year']),
                        ':result' => trim($edu['result']),
                    ]);
                }
            }

            header("Location: index.php");
            exit;
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
<title>Add Marriage Biodata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: linear-gradient(135deg, #6e48aa, #9d50bb); padding: 30px; }
  .container { max-width: 900px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
  h2 { color: #2c3e50; margin-bottom: 30px; font-weight: bold; text-align: center; }
  .form-control { border-radius: 10px; padding: 12px; transition: border-color 0.3s; }
  .btn-primary { background: #28a745; border: none; border-radius: 10px; padding: 12px; font-size: 18px; }
  .btn-primary:hover { background: #1e7e34; }
  .errors { color: #dc3545; margin-bottom: 20px; }
  .is-invalid { border-color: #dc3545 !important; }
  .invalid-feedback { display: none; color: #dc3545; font-size: 0.9em; position: relative; }
  .invalid-feedback i { margin-right: 5px; }
  .form-label { font-weight: bold; color: #2c3e50; }
  .edu-row { background: #f8f9fa; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
  #add-edu-btn { background: #3498db; border-radius: 10px; }
  a.back-link { color: #3498db; font-weight: bold; text-decoration: none; }
  a.back-link:hover { text-decoration: underline; }
  .form-control:focus { box-shadow: 0 0 5px rgba(0,123,255,0.5); }
  .input-group { position: relative; }
  .invalid-icon { display: none; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #dc3545; }
  .is-invalid ~ .invalid-icon { display: block; }
  .checkbox-group { display: flex; flex-wrap: wrap; }
  .checkbox-group label { margin-right: 20px; }
</style>
</head>
<body>
<div class="container">
<h2><i class="fas fa-plus-circle"></i> Add Marriage Biodata</h2>
<a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to List</a>
<?php if ($errors): ?>
    <div class="errors alert alert-danger mt-3">
        <ul>
        <?php foreach ($errors as $e): ?>
            <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form method="POST" enctype="multipart/form-data" id="addForm" novalidate>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Full Name *</label>
                <div class="input-group">
                    <input type="text" name="fullName" class="form-control" value="<?= htmlspecialchars($_POST['fullName'] ?? '') ?>" required>
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Full Name is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Father's Name</label>
                <input type="text" name="fatherName" class="form-control" value="<?= htmlspecialchars($_POST['fatherName'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Mother's Name</label>
                <input type="text" name="motherName" class="form-control" value="<?= htmlspecialchars($_POST['motherName'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Date of Birth *</label>
                <div class="input-group">
                    <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" required>
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> DOB is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Gender *</label>
                <div class="input-group">
                    <select name="gender" class="form-control" required>
                        <option value="">Select</option>
                        <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Gender is required.</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Marital Status</label>
                <select name="maritalStatus" class="form-control">
                    <option value="">Select</option>
                    <option value="Never Married" <?= ($_POST['maritalStatus'] ?? '') === 'Never Married' ? 'selected' : '' ?>>Never Married</option>
                    <option value="Divorced" <?= ($_POST['maritalStatus'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                    <option value="Widowed" <?= ($_POST['maritalStatus'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Religion</label>
                <select name="religion" class="form-control">
                    <option value="">Select</option>
                    <option value="Hindu" <?= ($_POST['religion'] ?? '') === 'Hindu' ? 'selected' : '' ?>>Hindu</option>
                    <option value="Muslim" <?= ($_POST['religion'] ?? '') === 'Muslim' ? 'selected' : '' ?>>Muslim</option>
                    <option value="Christian" <?= ($_POST['religion'] ?? '') === 'Christian' ? 'selected' : '' ?>>Christian</option>
                    <option value="Other" <?= ($_POST['religion'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Height (cm)</label>
                <div class="input-group">
                    <input type="number" name="height" min="100" max="250" class="form-control" value="<?= htmlspecialchars($_POST['height'] ?? '') ?>">
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Height must be 100-250 cm.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Occupation *</label>
                <div class="input-group">
                    <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($_POST['occupation'] ?? '') ?>" required>
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Occupation is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email *</label>
                <div class="input-group">
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Valid email is required.</div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Phone *</label>
                <div class="input-group">
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required pattern="\d{10,15}">
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Phone must be 10-15 digits.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">About Yourself</label>
                <textarea name="about" rows="4" class="form-control"><?= htmlspecialchars($_POST['about'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Hobby</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="hobby[]" value="Reading" <?= in_array('Reading', explode(', ', $_POST['hobby'] ?? '')) ? 'checked' : '' ?>> Reading</label>
                    <label><input type="checkbox" name="hobby[]" value="Traveling" <?= in_array('Traveling', explode(', ', $_POST['hobby'] ?? '')) ? 'checked' : '' ?>> Traveling</label>
                    <label><input type="checkbox" name="hobby[]" value="Sports" <?= in_array('Sports', explode(', ', $_POST['hobby'] ?? '')) ? 'checked' : '' ?>> Sports</label>
                    <label><input type="checkbox" name="hobby[]" value="Music" <?= in_array('Music', explode(', ', $_POST['hobby'] ?? '')) ? 'checked' : '' ?>> Music</label>
                    <label><input type="checkbox" name="hobby[]" value="Other" <?= in_array('Other', explode(', ', $_POST['hobby'] ?? '')) ? 'checked' : '' ?>> Other</label>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Profile Picture</label>
                <div class="input-group">
                    <input type="file" name="profile_picture" class="form-control" accept="image/jpeg,image/png,image/gif">
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Invalid file type or size.</div>
            </div>
            <hr>
            <h3><i class="fas fa-graduation-cap"></i> Educational Qualifications</h3>
            <div id="education-container"></div>
            <button type="button" id="add-edu-btn" class="btn btn-secondary"><i class="fas fa-plus"></i> Add More</button>
        </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save"></i> Save Biodata</button>
</form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function addEducationRow(data = {}) {
    const container = document.getElementById('education-container');
    const div = document.createElement('div');
    div.className = 'edu-row';
    div.innerHTML = `
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Degree *</label>
                <div class="input-group">
                    <input type="text" name="education[][degree]" class="form-control" value="${data.degree || ''}" required>
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Degree is required.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Institution *</label>
                <div class="input-group">
                    <input type="text" name="education[][institution]" class="form-control" value="${data.institution || ''}" required>
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Institution is required.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Year</label>
                <div class="input-group">
                    <input type="text" name="education[][year]" class="form-control" value="${data.year || ''}">
                    <i class="fas fa-exclamation-circle invalid-icon"></i>
                </div>
                <div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Year should be a valid year (optional).</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Result</label>
                <input type="text" name="education[][result]" class="form-control" value="${data.result || ''}">
                <button type="button" class="remove-btn btn btn-danger mt-2"><i class="fas fa-trash"></i> Remove</button>
            </div>
        </div>
    `;
    container.appendChild(div);
    div.querySelector('.remove-btn').addEventListener('click', () => div.remove());
}

document.getElementById('add-edu-btn').addEventListener('click', () => addEducationRow());
window.onload = () => addEducationRow();

document.getElementById('addForm').addEventListener('input', function(e) {
    const input = e.target;
    if (input.checkValidity()) {
        input.classList.remove('is-invalid');
        input.parentElement.querySelector('.invalid-icon').style.display = 'none';
    } else {
        input.classList.add('is-invalid');
        input.parentElement.querySelector('.invalid-icon').style.display = 'block';
    }
});

document.getElementById('addForm').addEventListener('submit', function(e) {
    let valid = true;
    const form = e.target;
    form.querySelectorAll('[required], [pattern]').forEach(input => {
        input.classList.remove('is-invalid');
        input.parentElement.querySelector('.invalid-icon').style.display = 'none';
        if (!input.checkValidity()) {
            input.classList.add('is-invalid');
            input.parentElement.querySelector('.invalid-icon').style.display = 'block';
            valid = false;
        }
    });
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>