<?php
// DB-backed infographic update for barangay — updates only the selected record by id
require_once __DIR__ . '/audit_brgy.php';

$target_dir = "uploads/infographics/";
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'arthurnerritech_library';
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die('DB connection error: ' . $conn->connect_error);

if (!isset($_GET['id'])) die('Infographic id missing.');
$id = (int)$_GET['id'];

// Fetch target infographic — ensure we're only operating on this row
$stmt = $conn->prepare('SELECT * FROM infograph WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) die('Infographic not found.');
$infograph = $res->fetch_assoc();
$stmt->close();

$error = '';
$upload_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['infographic_title'] ?? '');
    $theme = trim($_POST['infographic_theme'] ?? '');
    $date_added = $_POST['date_added'] ?? '';

    if (empty($title) || empty($theme) || empty($date_added)) {
        $error = 'Please fill in all fields.';
    } else {
        $new_image = null; // null means "no change"

        // If a new file is uploaded, save to disk and set $new_image to the filename
        if (!empty($_FILES['infographic_image']['tmp_name'])) {
            $image_tmp = $_FILES['infographic_image']['tmp_name'];
            $image_name = basename($_FILES['infographic_image']['name']);
            $target_file = $target_dir . $image_name;
            $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_images = ['jpg','jpeg','png','gif','webp'];

            if (!in_array($ext, $allowed_images)) {
                $error = 'Only JPG, PNG, GIF, and WebP images are allowed.';
            } else {
                if (file_exists($target_file)) {
                    $image_name = time() . '_' . $image_name;
                    $target_file = $target_dir . $image_name;
                }
                if (!move_uploaded_file($image_tmp, $target_file)) {
                    $error = 'Error uploading new image.';
                } else {
                    // delete old file if it exists and is stored on disk (not blob)
                    if (!empty($infograph['image']) && file_exists($target_dir . $infograph['image'])) {
                        @unlink($target_dir . $infograph['image']);
                    }
                    $new_image = $image_name;
                }
            }
        }

        if ($error === '') {
            if ($new_image !== null) {
                $upd = $conn->prepare('UPDATE infograph SET title = ?, theme = ?, date_added = ?, image = ? WHERE id = ?');
                $upd->bind_param('ssssi', $title, $theme, $date_added, $new_image, $id);
            } else {
                $upd = $conn->prepare('UPDATE infograph SET title = ?, theme = ?, date_added = ? WHERE id = ?');
                $upd->bind_param('sssi', $title, $theme, $date_added, $id);
            }

            if ($upd->execute()) {
                $upload_msg = '✅ Infographic updated successfully!';
                if (function_exists('log_brgy_action')) {
                    log_brgy_action('update_infographic', $title . ' | id: ' . $id);
                }
                // Refresh local copy from DB to reflect exact stored values
                $stmt = $conn->prepare('SELECT * FROM infograph WHERE id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $infograph = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $error = 'Database error: ' . $conn->error;
            }
            $upd->close();
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit Infographic</title>
<style>
body { font-family: "Segoe UI", Arial, sans-serif; margin:0; background: rgba(75,26,26,1); color:#222; }
.container { max-width:1000px; margin:20px auto; background:#fff; padding:30px 40px; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,0.08); border:1px solid #e1e5ea; }
h2{margin-top:0;text-align:center;font-size:1.9rem;color:#1a4e8a;border-bottom:2px solid #e6ebf1;padding-bottom:10px}
.form-layout{display:grid;grid-template-columns:2fr 1fr;gap:30px;margin-top:20px}
label{display:block;margin-bottom:6px;font-weight:600;color:#333}
input{padding:10px 12px;width:95%;border:1px solid #ccc;border-radius:6px;font-size:0.95rem;background:#fafafa}
button{padding:10px 12px;width:80%;border:1px solid #ccc;border-radius:6px;font-size:0.95rem;background:#fafafa}
input:focus{outline:none;border-color:#1a4e8a;background:#fff;box-shadow:0 0 4px rgba(26,78,138,0.2)}
button{background:#143a66;color:white;border:none;cursor:pointer;font-weight:600;padding:12px 25px;border-radius:6px}
button:hover{background:rgba(255,149,49,1)}
.button-wrapper{grid-column:1 / -1;text-align:center;margin-top:25px}
.cover-preview{display:flex;justify-content:center;align-items:flex-start;border:1px solid #ddd;border-radius:6px;background:#fafafa;padding:10px;max-height:300px;overflow:hidden;margin-top:10px}
.cover-preview img{max-width:100%;max-height:280px;border-radius:6px}
.upload-message{text-align:center;margin-bottom:15px;font-weight:600}
.upload-message.success{color:green}
.upload-message.error{color:red}
</style>
</head>
<body>
<div class="container">
    <h2>✏️ Edit Infographic</h2>

    <?php if ($error) echo "<p class='upload-message error'>".htmlspecialchars($error)."</p>"; ?>
    <?php if ($upload_msg) echo "<p class='upload-message success'>".htmlspecialchars($upload_msg)."</p>"; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-layout">
            <div>
                <label>Title:</label>
                <input type="text" name="infographic_title" value="<?= htmlspecialchars($infograph['title']) ?>" required>

                <label>Date Added:</label>
                <input type="date" name="date_added" value="<?= htmlspecialchars($infograph['date_added'] ?? $infograph['date']) ?>" required>

                <label>Theme:</label>
                <input type="text" name="infographic_theme" value="<?= htmlspecialchars($infograph['theme']) ?>" required>

                <label>Replace Image (optional):</label>
                <div class="hint">Current file: <?= htmlspecialchars($infograph['image'] ?? '') ?></div>
                <input type="file" name="infographic_image" id="infographicImage" accept="image/*">
                <div class="file-name" id="fileName"></div>
            </div>

            <div class="cover-preview">
                <?php if (!empty($infograph['image']) && file_exists($target_dir . $infograph['image'])): ?>
                    <img src="<?= $target_dir . htmlspecialchars($infograph['image']) ?>" alt="Infographic" id="currentImage">
                <?php elseif (!empty($infograph['image']) && strpos($infograph['image'], '/') === false): ?>
                    <!-- If image stored as filename but file missing -->
                    <p>Image file not found on disk.</p>
                <?php elseif (!empty($infograph['image'])): ?>
                    <!-- If image contains blob/base64 (older storage), attempt to render -->
                    <img src="data:image/jpeg;base64,<?= base64_encode($infograph['image']) ?>" alt="Infographic" id="currentImage">
                <?php else: ?>
                    <p>No image uploaded.</p>
                <?php endif; ?>
            </div>

            <div class="button-wrapper">
                <button type="submit">💾 Save Changes</button>
                <br><br>
                <button type="button" onclick="window.location.href='brgy_infograph.php'" style="background:#666; margin-top:10px; padding: 12px 25px; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    ← Back to Infographics
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// File name preview
const fileInput = document.getElementById('infographicImage');
if (fileInput) fileInput.addEventListener('change', function(){
    document.getElementById('fileName').textContent = this.files.length ? "Selected: " + this.files[0].name : "";
});
</script>
</body>
</html>
