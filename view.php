<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$biodata = null;
$educations = [];

try {
    // Prepare the statement with a dynamic condition for user access
    $sql = "SELECT b.*, u.username FROM biodata b JOIN users u ON b.user_id = u.id WHERE b.id = ?";
    $params = [$id];

    // If the user is not an admin, add a condition to only fetch their own records
    if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
        $sql .= " AND b.user_id = ?";
        $params[] = $_SESSION['user_id'];
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $biodata = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no biodata is found after the query, it means either the ID is invalid
    // or the user does not have permission.
    if (!$biodata) {
        header("Location: index.php");
        exit;
    }

    // Fetch educational qualifications for this biodata
    $eduStmt = $conn->prepare("SELECT * FROM educational_qualification WHERE biodata_id = ?");
    $eduStmt->execute([$id]);
    $educations = $eduStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>View Biodata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    body { background: linear-gradient(135deg, #6e48aa, #9d50bb); padding: 40px; }
    .container { max-width: 900px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
    h2 { color: #2c3e50; text-align: center; margin-bottom: 40px; font-weight: bold; }
    .profile-img { width: 180px; height: 180px; border-radius: 50%; object-fit: cover; border: 5px solid #3498db; margin-bottom: 30px; }
    .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
    .card-header { background: #3498db; color: white; font-weight: bold; border-radius: 15px 15px 0 0; padding: 20px; }
    .list-group-item { border: none; padding: 15px; font-size: 1.1em; }
    .list-group-item strong { color: #2c3e50; }
    .back-link { color: #3498db; font-weight: bold; text-decoration: none; }
    .back-link:hover { text-decoration: underline; }
    .btn-warning, .btn-danger { border-radius: 10px; padding: 10px 20px; }
    ul.education-list { padding-left: 20px; }
</style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to List</a>
    <h2><i class="fas fa-user-circle"></i> Biodata Details</h2>
    <div class="text-center">
        <?php
        $profilePicPath = htmlspecialchars($biodata['profile_picture'] ?? 'uploads/placeholder.png');
        if (!file_exists($profilePicPath) || is_dir($profilePicPath)) {
            $profilePicPath = 'uploads/placeholder.png';
        }
        ?>
        <img src="<?= $profilePicPath ?>" alt="Profile" class="profile-img">
    </div>
    <div class="card">
        <div class="card-header"><i class="fas fa-info-circle"></i> Personal Information</div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Full Name:</strong> <?= htmlspecialchars($biodata['fullName']) ?></li>
            <li class="list-group-item"><strong>Username:</strong> <?= htmlspecialchars($biodata['username']) ?></li>
            <li class="list-group-item"><strong>Father's Name:</strong> <?= htmlspecialchars($biodata['fatherName']) ?></li>
            <li class="list-group-item"><strong>Mother's Name:</strong> <?= htmlspecialchars($biodata['motherName']) ?></li>
            <li class="list-group-item"><strong>Date of Birth:</strong> <?= htmlspecialchars($biodata['dob']) ?></li>
            <li class="list-group-item"><strong>Gender:</strong> <?= htmlspecialchars($biodata['gender']) ?></li>
            <li class="list-group-item"><strong>Marital Status:</strong> <?= htmlspecialchars($biodata['maritalStatus']) ?></li>
            <li class="list-group-item"><strong>Religion:</strong> <?= htmlspecialchars($biodata['religion']) ?></li>
            <li class="list-group-item"><strong>Height (cm):</strong> <?= htmlspecialchars($biodata['height']) ?></li>
            <li class="list-group-item"><strong>Occupation:</strong> <?= htmlspecialchars($biodata['occupation']) ?></li>
            <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($biodata['email']) ?></li>
            <li class="list-group-item"><strong>Phone:</strong> <?= htmlspecialchars($biodata['phone']) ?></li>
            <li class="list-group-item"><strong>Hobby:</strong> <?= htmlspecialchars($biodata['hobby'] ?: 'N/A') ?></li>
            <li class="list-group-item"><strong>About:</strong> <?= nl2br(htmlspecialchars($biodata['about'])) ?></li>
        </ul>
    </div>
    <div class="card">
        <div class="card-header"><i class="fas fa-graduation-cap"></i> Educational Qualifications</div>
        <ul class="list-group list-group-flush">
            <?php if ($educations): ?>
                <?php foreach ($educations as $edu): ?>
                    <li class="list-group-item">
                        <strong>Degree:</strong> <?= htmlspecialchars($edu['degree']) ?><br>
                        <strong>Institution:</strong> <?= htmlspecialchars($edu['institution']) ?><br>
                        <strong>Year:</strong> <?= htmlspecialchars($edu['year']) ?><br>
                        <strong>Result:</strong> <?= htmlspecialchars($edu['result']) ?>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-group-item text-center">No qualifications added.</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $biodata['user_id'] === $_SESSION['user_id'])): ?>
        <div class="text-center">
            <a href="edit.php?id=<?= htmlspecialchars($id) ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
            <a href="delete.php?id=<?= htmlspecialchars($id) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fas fa-trash"></i> Delete</a>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>