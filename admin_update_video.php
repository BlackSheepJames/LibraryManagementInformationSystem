<?php

$data_file = "videos.json";
require_once __DIR__ . '/auth.php';
require_role('mdrrmOfficer');
$target_dir = "uploads/videos/";

$videos = file_exists($data_file) ? json_decode(file_get_contents($data_file), true) : [];

// Support two storage backends: JSON file (legacy) and MySQL DB (admin_watch.php uses DB)
$is_db_backed = false;
$db_conn = null;

if (!isset($_GET['id'])) {
    die("Video not found.");
}

$id = (int)$_GET['id'];

if (isset($videos[$id])) {
    $video = $videos[$id];
} else {
    // Try to load from database (mirror of admin_watch.php connection)
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname   = "arthurnerritech_library";

    $db_conn = new mysqli($servername, $username, $password, $dbname);
    if ($db_conn->connect_error) {
        // cannot find the video in JSON and DB connection failed
        die("Video not found.");
    }

    $stmt = $db_conn->prepare("SELECT id, title, theme, date_added, publisher, file_name FROM videos WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            // normalize field names to match JSON-based structure
            $video = [
                'id' => $row['id'],
                'title' => $row['title'],
                'theme' => $row['theme'],
                'date' => $row['date_added'],
                'publisher' => $row['publisher'],
                'file' => $row['file_name']
            ];
            $is_db_backed = true;
        } else {
            die("Video not found.");
        }
        $stmt->close();
    } else {
        die("Video not found.");
    }
}

$error = '';
$upload_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['video_title']);
    $theme = trim($_POST['video_theme']);
    $date_added = $_POST['date_added'];
    $publisher = trim($_POST['publisher']);

    if (empty($title) || empty($theme) || empty($date_added) || empty($publisher)) {
        $error = "Please fill in all fields.";
    } else {
        // Replace video file if new one uploaded
        if (!empty($_FILES['video_file']['name'])) {
            $file_name = basename($_FILES["video_file"]["name"]);
            $target_file = $target_dir . $file_name;
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];

            if (!in_array($file_type, $allowed_types)) {
                $error = "Only video files (mp4, webm, ogg, mov, avi, mkv) are allowed.";
            } else {
                if (file_exists($target_file)) {
                    // Prevent overwrite by renaming
                    $file_name = time() . '_' . $file_name;
                    $target_file = $target_dir . $file_name;
                }
                if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
                    // Delete old video file
                    $old_file = $target_dir . $video['file'];
                    if (!empty($video['file']) && file_exists($old_file)) {
                        unlink($old_file);
                    }
                    $video['file'] = $file_name;
                } else {
                    $error = "Error uploading new video file.";
                }
            }
        }

        if ($error === '') {
            $video['title'] = $title;
            $video['theme'] = $theme;
            $video['date'] = $date_added;
            $video['publisher'] = $publisher;

            if ($is_db_backed && $db_conn) {
                // update DB row
                $stmt = $db_conn->prepare("UPDATE videos SET title = ?, theme = ?, date_added = ?, publisher = ?, file_name = ? WHERE id = ?");
                if ($stmt) {
                    $fileForDb = $video['file'];
                    $stmt->bind_param('sssssi', $video['title'], $video['theme'], $video['date'], $video['publisher'], $fileForDb, $video['id']);
                    if ($stmt->execute()) {
                        $upload_msg = "✅ Video updated successfully (database).";
                    } else {
                        $error = "Failed to update database: " . htmlspecialchars($stmt->error);
                    }
                    $stmt->close();
                } else {
                    $error = "Database error: " . htmlspecialchars($db_conn->error);
                }
            } else {
                // JSON-backed update
                $videos[$id] = $video;
                file_put_contents($data_file, json_encode($videos, JSON_PRETTY_PRINT));
                $upload_msg = "✅ Video updated successfully!";
            }
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
                <input type="date" name="date_added" value="<?= htmlspecialchars($video['date']) ?>" required>

                <label>Theme:</label>
                <input type="text" name="video_theme" value="<?= htmlspecialchars($video['theme']) ?>" required>

                <label>Publisher:</label>
                <input type="text" name="publisher" value="<?= htmlspecialchars($video['publisher']) ?>" required>

                <label>Replace Video File (optional):</label>
                <div class="hint">Current file: <?= htmlspecialchars($video['file']) ?></div>
                <input type="file" name="video_file" id="videoFile" accept="video/*">
                <div class="file-name" id="fileName"></div>
            </div>

            <div class="video-preview">
                <?php if (!empty($video['file']) && file_exists($target_dir . $video['file'])): ?>
                    <video controls preload="metadata">
                        <source src="<?= $target_dir . htmlspecialchars($video['file']) ?>" type="video/<?= pathinfo($video['file'], PATHINFO_EXTENSION) ?>">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <p>No video uploaded.</p>
                <?php endif; ?>
            </div>

            <div class="button-wrapper">
                <button type="submit">💾 Save Changes</button>
                <br><br>
                <button type="button" onclick="window.location.href='admin_watch.php'" style="background:#666; margin-top:10px; padding: 12px 25px; color: white; border: none; border-radius: 6px; cursor: pointer;">
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
