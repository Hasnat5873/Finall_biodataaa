<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// Role-based where clause
$where = ($_SESSION['role'] === 'admin') ? '' : 'WHERE b.user_id = :user_id';

// Fetch biodata records with usernames
$stmt = $conn->prepare("
    SELECT b.*, u.username 
    FROM biodata b 
    JOIN users u ON b.user_id = u.id 
    $where 
    ORDER BY b.created_at DESC
");

if ($_SESSION['role'] !== 'admin') {
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
} else {
    $stmt->execute();
}

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Collect biodata IDs for education fetch
$biodata_ids = array_column($records, 'id');

if (!empty($biodata_ids)) {
    // Prepare placeholders for IN query
    $placeholders = implode(',', array_fill(0, count($biodata_ids), '?'));
    $eduStmt = $conn->prepare("SELECT * FROM educational_qualification WHERE biodata_id IN ($placeholders)");
    $eduStmt->execute($biodata_ids);
    $educationsRaw = $eduStmt->fetchAll(PDO::FETCH_ASSOC);

    // Group educations by biodata_id
    $educations = [];
    foreach ($educationsRaw as $edu) {
        $educations[$edu['biodata_id']][] = $edu;
    }
} else {
    $educations = [];
}

// Get profile picture for logged-in user
$stmt = $conn->prepare("SELECT profile_picture FROM biodata WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();
$profilePicture = $profile && $profile['profile_picture'] ? $profile['profile_picture'] : 'Uploads/placeholder.png';

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>All Marriage Biodata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: linear-gradient(135deg, #6e48aa, #9d50bb); padding: 30px; }
  .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); margin-bottom: 30px; }
  h2 { color: #2c3e50; font-weight: bold; }
  .profile-img-header { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #3498db; }
  .top-links { text-align: center; margin-bottom: 30px; }
  .top-links a { color: #fff; background: #3498db; padding: 10px 20px; border-radius: 10px; text-decoration: none; margin: 0 10px; }
  .top-links a:hover { background: #2874a6; }
  .card { border: none; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
  table { width: 100%; border-collapse: collapse; }
  th { background: #3498db; color: white; padding: 15px; font-weight: bold; }
  td { padding: 15px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
  tr:nth-child(even) { background: #f8f9fa; }
  .profile-img { max-width: 60px; border-radius: 10px; }
  .dropdown-menu { border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
  .dropdown-item:hover { background: #f1f1f1; }
  ul.education-list { list-style-type: none; padding-left: 0; margin: 0; }
  ul.education-list li { margin-bottom: 5px; }
</style>
</head>
<body>
<div class="header">
    <h2><i class="fas fa-users"></i> Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</h2>
    <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile Picture" class="profile-img-header">
</div>
<div class="top-links">
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <a href="add.php"><i class="fas fa-plus"></i> Add New Biodata</a>
</div>
<div class="card">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Profile Picture</th>
                <th>User</th>
                <th>Full Name</th>
                <th>Father Name</th>
                <th>Mother Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Marital Status</th>
                <th>Religion</th>
                <th>Height</th>
                <th>Occupation</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Educational Qualifications</th>
                <th>About</th>
                <th>Last Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($records): ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><img src="<?= htmlspecialchars($row['profile_picture'] ?? 'Uploads/placeholder.png') ?>" alt="Profile" class="profile-img"></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['fullName']) ?></td>
                        <td><?= htmlspecialchars($row['fatherName']) ?></td>
                        <td><?= htmlspecialchars($row['motherName']) ?></td>
                        <td><?= htmlspecialchars($row['dob']) ?></td>
                        <td><?= htmlspecialchars($row['gender']) ?></td>
                        <td><?= htmlspecialchars($row['maritalStatus']) ?></td>
                        <td><?= htmlspecialchars($row['religion']) ?></td>
                        <td><?= htmlspecialchars($row['height']) ?></td>
                        <td><?= htmlspecialchars($row['occupation']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td>
                            <?php if (!empty($educations[$row['id']])): ?>
                                <ul class="education-list">
                                    <?php foreach ($educations[$row['id']] as $edu): ?>
                                        <li>
                                            <strong><?= htmlspecialchars($edu['degree']) ?></strong>, 
                                            <?= htmlspecialchars($edu['institution']) ?>, 
                                            <?= htmlspecialchars($edu['year']) ?>, 
                                            <?= htmlspecialchars($edu['result']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['about']) ?></td>
                        <td><?= htmlspecialchars($row['updated_at']) ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i> Actions</button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="view.php?id=<?= $row['id'] ?>"><i class="fas fa-eye"></i> View</a></li>
                                    <?php if ($_SESSION['role'] === 'admin' || $row['user_id'] === $_SESSION['user_id']): ?>
                                        <li><a class="dropdown-item" href="edit.php?id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a></li>
                                        <li><a class="dropdown-item" href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="18" class="text-center">No biodata found. <a href="add.php">Add new</a>.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
