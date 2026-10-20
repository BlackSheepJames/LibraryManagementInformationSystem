<?php
// Database connection
$servername = "localhost";
$username = "root";      // Default XAMPP MySQL username
$password = "";          // Default XAMPP MySQL password is empty
$dbname   = "arthurnerritech_library";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Require brgyOfficer or mdrrmOfficer
require_once __DIR__ . '/auth.php';
require_role('mdrrmOfficer');

// --- PATHS ---
$target_dir = "uploads/modules/";
$cover_dir  = "uploads/covers/";
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
if (!is_dir($cover_dir))  mkdir($cover_dir, 0777, true);

// --- GET MODULE ---
if (!isset($_GET['id'])) { die("Module ID not provided."); }
$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM modules WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$module = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$module) { die("Module not found."); }

$error = "";

// --- HANDLE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['module_title']);
    $theme = trim($_POST['module_theme']);
    $date_added = $_POST['date_added'];

    // keep current names unless replaced
    $new_file_name  = $module['file_name']  ?? "";
    $new_cover_name = $module['cover_name'] ?? "";

    // Replace module file (optional)
    if (!empty($_FILES['module_file']['name'])) {
        $file_name   = basename($_FILES["module_file"]["name"]);
        $target_file = $target_dir . $file_name;
        $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['pdf','doc','docx','ppt','pptx','zip'];

        if (in_array($ext, $allowed_types) && move_uploaded_file($_FILES["module_file"]["tmp_name"], $target_file)) {
            // delete old file
            if (!empty($new_file_name) && file_exists($target_dir . $new_file_name)) {
                @unlink($target_dir . $new_file_name);
            }
            $new_file_name = $file_name;
        } else {
            $error = "Invalid file type or upload error for module file.";
        }
    }

    // Replace cover (optional)
    if (!empty($_FILES['cover_photo']['name'])) {
        $cover_name  = basename($_FILES["cover_photo"]["name"]);
        $cover_file  = $cover_dir . $cover_name;
        $ext = strtolower(pathinfo($cover_file, PATHINFO_EXTENSION));
        $allowed_images = ['jpg','jpeg','png','gif','webp'];

        if (in_array($ext, $allowed_images) && move_uploaded_file($_FILES["cover_photo"]["tmp_name"], $cover_file)) {
            // delete old cover
            if (!empty($new_cover_name) && file_exists($cover_dir . $new_cover_name)) {
                @unlink($cover_dir . $new_cover_name);
            }
            $new_cover_name = $cover_name;
        } else {
            $error = "Invalid file type or upload error for cover photo.";
        }
    }

    if (empty($error)) {
        $upd = $conn->prepare("UPDATE modules 
            SET title = ?, theme = ?, date_added = ?, file_name = ?, cover_name = ?
            WHERE id = ?");
        $upd->bind_param("sssssi", $title, $theme, $date_added, $new_file_name, $new_cover_name, $id);
        if ($upd->execute()) {
            $upd->close();
            header("Location: admin_trainmods.php");
            exit;
        }
        $error = "Database error: " . $conn->error;
        $upd->close();
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit Module</title>
<style>
body {font-family: "Segoe UI", Arial, sans-serif; margin: 0; background: rgba(75, 26, 26, 1); color: #222;}
.container {max-width: 1000px; margin: 20px auto; background: #fff; padding: 30px 40px; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); border: 1px solid #e1e5ea;}
h2 {margin-top: 0; text-align: center; font-size: 1.9rem; color: #1a4e8a; border-bottom: 2px solid #e6ebf1; padding-bottom: 10px;}
.form-layout {display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 20px;}
label {display: block; margin-bottom: 6px; font-weight: 600; color: #333;}
input {padding: 10px 12px; width: 95%; border: 1px solid #ccc; border-radius: 6px; font-size: 0.95rem; background: #fafafa;}
input:focus {outline: none; border-color: #1a4e8a; background: #fff; box-shadow: 0 0 4px rgba(26,78,138,0.2);}
button {background: #143a66; color: white; border: none; cursor: pointer; font-weight: 600; padding: 12px 25px; border-radius: 6px;}
button:hover {background: rgba(255, 149, 49, 1);}
.button-wrapper {grid-column: 1 / -1; text-align: center; margin-top: 25px;}
.cover-preview {display: flex; justify-content: center; align-items: flex-start; border: 1px solid #ddd; border-radius: 6px; background: #fafafa; padding: 10px; max-height: 300px; overflow: hidden; margin-top: 40px;}
.cover-preview img {max-width: 100%; max-height: 280px; border-radius: 6px;}
.hint {font-size: 0.85rem; color: #666; margin-bottom: 6px;}
.file-name {font-size: 0.9rem; margin-top: 6px; color: #333; font-style: italic;}
</style>
</head>
<body>
<div class="container">
    <h2>✏️ Edit Module</h2>

    <?php if (!empty($error)) echo "<p style='color:red; text-align:center;'>".htmlspecialchars($error)."</p>"; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-layout">
            <div>
                <label>Title:</label>
                <input type="text" name="module_title" value="<?= htmlspecialchars($module['title'] ?? '') ?>" required>

                <label>Date Added:</label>
                <input type="date" name="date_added" value="<?= htmlspecialchars($module['date_added'] ?? '') ?>" required>

                <label>Theme:</label>
                <input type="text" name="module_theme" value="<?= htmlspecialchars($module['theme'] ?? '') ?>" required>

                <label>Replace File (optional):</label>
                <div class="hint">Current file: <?= htmlspecialchars($module['file_name'] ?? 'N/A') ?></div>
                <input type="file" name="module_file" id="moduleFile" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip">
                <div class="file-name" id="fileName"></div>

                <label>Replace Cover Photo (optional):</label>
                <input type="file" name="cover_photo" accept="image/*" id="coverPhoto">
                <img id="newCoverPreview" alt="New Cover Preview" style="margin-top:8px; max-width:150px; display:none; border-radius:6px;">
            </div>

            <!-- Right side cover preview -->
            <div class="cover-preview">
                <?php
                  $coverPath = $cover_dir . ($module['cover_name'] ?? '');
                  if (!empty($module['cover_name']) && file_exists($coverPath)):
                ?>
                    <img src="<?= htmlspecialchars($coverPath) ?>" alt="Cover" id="currentCover">
                <?php else: ?>
                    <p>No cover uploaded.</p>
                <?php endif; ?>
            </div>

            <!-- Centered Save Button -->
            <div class="button-wrapper">
                <button type="submit">💾 Save Changes</button>
            </div>
        </div>
    </form>
</div>

<script>
// Show selected module file name
document.getElementById('moduleFile').addEventListener('change', function(){
    document.getElementById('fileName').textContent = this.files.length ? "Selected: " + this.files[0].name : "";
});

// Preview newly chosen cover
document.getElementById('coverPhoto').addEventListener('change', function(){
    const preview = document.getElementById('newCoverPreview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { 
            preview.src = e.target.result; 
            preview.style.display = 'block';
        };
        reader.readAsDataURL(this.files[0]);
    } else {
        preview.src = "";
        preview.style.display = 'none';
    }
});
</script>
</body>
</html>
