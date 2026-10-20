<?php
// Switch to DB-backed editing for videos so actions are persisted server-side
// and audit entries are reliably created.
require_once __DIR__ . '/audit_brgy.php';

$target_dir = "uploads/videos/";
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'arthurnerritech_library';
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die('DB Connection failed: ' . $conn->connect_error);
}

$error = '';
$upload_msg = '';

if (!isset($_GET['id'])) {
    die('Video id missing.');
}
$id = (int)$_GET['id'];

$stmt = $conn->prepare('SELECT * FROM videos WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    die('Video not found.');
}
$video = $res->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['video_title'] ?? '');
    $theme = trim($_POST['video_theme'] ?? '');
    $date_added = $_POST['date_added'] ?? '';
    $publisher = trim($_POST['publisher'] ?? '');

    if (empty($title) || empty($theme) || empty($date_added) || empty($publisher)) {
        $error = 'Please fill in all fields.';
    } else {
        $new_file = $video['file_name'];

        // Replace video file if new one uploaded
        if (!empty($_FILES['video_file']['name'])) {
            $file_name = basename($_FILES['video_file']['name']);
            $target_file = $target_dir . $file_name;
            $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['mp4','webm','ogg','mov','avi','mkv'];

            if (!in_array($ext, $allowed_types)) {
                $error = 'Only video files (mp4, webm, ogg, mov, avi, mkv) are allowed.';
            } else {
                if (file_exists($target_file)) {
                    $file_name = time() . '_' . $file_name;
                    $target_file = $target_dir . $file_name;
                }
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target_file)) {
                    // delete old file if present
                    if (!empty($video['file_name'])) {
                        $old = $target_dir . $video['file_name'];
                        if (file_exists($old)) @unlink($old);
                    }
                    $new_file = $file_name;
                } else {
                    $error = 'Error uploading new video file.';
                }
            }
        }

        if ($error === '') {
            $upd = $conn->prepare('UPDATE videos SET title = ?, theme = ?, date_added = ?, publisher = ?, file_name = ? WHERE id = ?');
            $upd->bind_param('sssssi', $title, $theme, $date_added, $publisher, $new_file, $id);
            if ($upd->execute()) {
                $upload_msg = '✅ Video updated successfully!';
                if (function_exists('log_brgy_action')) {
                    log_brgy_action('update_video', $title . ' | id: ' . $id);
                }
                // refresh $video
                $video['title'] = $title;
                $video['theme'] = $theme;
                $video['date_added'] = $date_added;
                $video['publisher'] = $publisher;
                $video['file_name'] = $new_file;
            } else {
                $error = 'Database error: ' . $conn->error;
            }
            $upd->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>✏️ Edit Video - <?= htmlspecialchars($video['title']) ?></title>
<style>
body {
    font-family: "Segoe UI", Arial, sans-serif;
    margin: 0;
    background: rgba(75, 26, 26, 1);
    color: #222;
}
.container {
    max-width: 1000px;
    margin: 20px auto;
    background: #fff;
    padding: 30px 40px;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid #e1e5ea;
}
h2 {
    margin-top: 0;
    text-align: center;
    font-size: 1.9rem;
    color: #1a4e8a;
    border-bottom: 2px solid #e6ebf1;
    padding-bottom: 10px;
}
.form-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-top: 20px;
}
label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}
input {
    padding: 10px 12px;
    margin-bottom: 17px;
    width: 95%;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 0.95rem;
    background: #fafafa;
}
button {
    padding: 10px 12px;
    width: 80%;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 0.95rem;
    background: #fafafa;
}
input:focus {
    outline: none;
    border-color: #1a4e8a;
    background: #fff;
    box-shadow: 0 0 4px rgba(26,78,138,0.2);
}
button {
    background: #143a66;
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 600;
    padding: 12px 25px;
    transition: background 0.2s ease;
    border-radius: 6px;
}
button:hover {
    background: rgba(255, 149, 49, 1);
}
.button-wrapper {
    grid-column: 1 / -1;
    text-align: center;
    margin-top: 25px;
}
.video-preview {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fafafa;
    padding: 10px;
    max-height: 180px;
    overflow: hidden;
    margin-top: 30px;
}
video {
    max-width: 100%;
    max-height: 280px;
    border-radius: 6px;
}
.upload-message {
    text-align: center;
    margin-bottom: 15px;
    font-weight: 600;
}
.upload-message.success {color: green;}
.upload-message.error {color: red;}
</style>
</head>
<body>
<div class="container">
    <h2>✏️ Edit Video</h2>

    <?php 
    if ($error) echo "<p class='upload-message error'>$error</p>"; 
    if ($upload_msg) echo "<p class='upload-message success'>$upload_msg</p>"; 
    ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-layout">
            <div>
                <label>Title:</label>
                <input type="text" name="video_title" value="<?= htmlspecialchars($video['title']) ?>" required>

                <label>Date Added:</label>
                <input type="date" name="date_added" value="<?= htmlspecialchars($video['date_added']) ?>" required>

                <label>Theme:</label>
                <input type="text" name="video_theme" value="<?= htmlspecialchars($video['theme']) ?>" required>

                <label>Publisher:</label>
                <input type="text" name="publisher" value="<?= htmlspecialchars($video['publisher']) ?>" required>

                <label>Replace Video File (optional):</label>
                <div class="hint">Current file: <?= htmlspecialchars($video['file_name']) ?></div>
                <input type="file" name="video_file" id="videoFile" accept="video/*">
                <div class="file-name" id="fileName"></div>
            </div>

            <div class="video-preview">
                <?php if (!empty($video['file_name']) && file_exists($target_dir . $video['file_name'])): ?>
                    <video controls preload="metadata">
                        <source src="<?= $target_dir . htmlspecialchars($video['file_name']) ?>" type="video/<?= pathinfo($video['file_name'], PATHINFO_EXTENSION) ?>">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <p>No video uploaded.</p>
                <?php endif; ?>
            </div>

            <div class="button-wrapper">
                <button type="submit">💾 Save Changes</button>
                <br><br>
                <button type="button" onclick="window.location.href='brgy_watch.php'" style="background:#666; margin-top:10px; padding: 12px 25px; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    ← Back to Videos
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// File name preview
document.getElementById('videoFile').addEventListener('change', function(){
    document.getElementById('fileName').textContent = this.files.length ? "Selected: " + this.files[0].name : "";
});
</script>
</body>
</html>
