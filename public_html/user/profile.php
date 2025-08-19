<?php
require_once __DIR__ . '/../includes/header.php';
require_member();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid CSRF token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_details') {
            $name = sanitize_string($_POST['name'] ?? '');
            $city = sanitize_string($_POST['city'] ?? '');
            $phone = sanitize_string($_POST['phone'] ?? '');
            $pdo->prepare('UPDATE users SET name = ?, city = ?, phone = ? WHERE id = ?')->execute([$name, $city, $phone, $user['id']]);
            $_SESSION['user']['name'] = $name; // Update session name
            set_flash('success', 'Profile details updated.');

        } elseif ($action === 'upload_avatar') {
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $max_size = 2 * 1024 * 1024; // 2MB
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

                if ($file['size'] > $max_size) {
                    set_flash('error', 'File is too large. Max size is 2MB.');
                } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed_types)) {
                    set_flash('error', 'Invalid file type. Only JPG, PNG, and GIF are allowed.');
                } else {
                    // Generate a unique name and path
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $new_filename = $user['id'] . '_' . time() . '.' . $extension;
                    $upload_path = __DIR__ . '/../uploads/avatars/' . $new_filename;

                    // Delete old avatar if it exists
                    $old_avatar_stmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
                    $old_avatar_stmt->execute([$user['id']]);
                    $old_avatar_path = $old_avatar_stmt->fetchColumn();
                    if ($old_avatar_path && file_exists(__DIR__ . '/../' . $old_avatar_path)) {
                        unlink(__DIR__ . '/../' . $old_avatar_path);
                    }

                    // Ensure the upload directory exists
                    $upload_dir = __DIR__ . '/../uploads/avatars/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    // Move the new file and update DB
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $db_path = '/uploads/avatars/' . $new_filename;
                        $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')->execute([$db_path, $user['id']]);
                        $_SESSION['user']['avatar_url'] = $db_path; // Update session avatar
                        set_flash('success', 'Profile picture updated.');
                    } else {
                        set_flash('error', 'Failed to upload file.');
                    }
                }
            } else {
                set_flash('error', 'No file uploaded or an error occurred.');
            }
        }
        header('Location: /user/profile.php');
        exit;
    }
}

$stmt = $pdo->prepare('SELECT name, email, city, phone, avatar_url FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$row = $stmt->fetch();

// Define a default avatar
$avatar = $row['avatar_url'] ?: 'https://placehold.co/100x100/EFEFEF/AAAAAA&text=Avatar';

foreach (get_flashes() as $f) {
  $color = $f['type'] === 'success' ? '#10b981' : '#e11d48';
  echo '<div class="card" style="border-left:4px solid ' . $color . ';margin-top:12px">' . htmlspecialchars($f['message']) . '</div>';
}
?>

<h2>My Profile</h2>
<div class="card" style="margin-top:12px">
  <div style="display: flex; align-items: flex-start; gap: 20px;">
    <img src="<?= htmlspecialchars($avatar) ?>" alt="Your Avatar" style="width: 100px; height: 100px; border-radius: 50%;">

    <form method="post" enctype="multipart/form-data" style="flex-grow: 1;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="upload_avatar">
      <label for="avatar">Update Profile Picture</label>
      <input type="file" id="avatar" name="avatar" accept="image/png, image/jpeg, image/gif" required>
      <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Max file size: 2MB. Allowed types: JPG, PNG, GIF.</div>
      <div style="margin-top:12px"><button class="btn btn-outline">Upload New Picture</button></div>
    </form>
  </div>

  <hr style="margin: 20px 0;">

  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="update_details">
    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>">
    <label>Email</label>
    <input type="email" value="<?= htmlspecialchars($row['email']) ?>" disabled>
    <label>City</label>
    <input type="text" name="city" value="<?= htmlspecialchars($row['city'] ?? '') ?>">
    <label>Phone</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($row['phone'] ?? '') ?>">
    <div style="margin-top:12px"><button class="btn">Save</button></div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


