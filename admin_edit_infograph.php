<?php
// edit_infograph.php

// Database connection
$servername = "localhost";
$username = "root";      // Default XAMPP MySQL username
$password = "";          // Default XAMPP MySQL password is empty
$dbname   = "arthurnerritech_library";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once __DIR__ . '/auth.php';
require_role('mdrrmOfficer');

// ===== Validate ID =====
if (!isset($_GET['id'])) {
    die("Infographic not found.");
}
$id = (int)$_GET['id'];

// ===== Fetch Infographic from DB =====
$stmt = $conn->prepare("SELECT * FROM infograph WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$infograph = $result->fetch_assoc();

if (!$infograph) {
    die("Infographic not found.");
}

$error = '';
$upload_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['infographic_title']);
    $theme = trim($_POST['infographic_theme']);
    $date_added = $_POST['date_added'];

    if (empty($title) || empty($theme) || empty($date_added)) {
        $error = "Please fill in all fields.";
    } else {
        // Update with/without new image
        if (!empty($_FILES['infographic_image']['name'])) {
            $imgData = file_get_contents($_FILES['infographic_image']['tmp_name']);
            $stmt = $conn->prepare("UPDATE infograph SET title=?, theme=?, date=?, image=? WHERE id=?");
            $stmt->bind_param("ssssi", $title, $theme, $date_added, $imgData, $id);
        } else {
            $stmt = $conn->prepare("UPDATE infograph SET title=?, theme=?, date=? WHERE id=?");
            $stmt->bind_param("sssi", $title, $theme, $date_added, $id);
        }

        if ($stmt->execute()) {
            $upload_msg = "✅ Infographic updated successfully!";
            // refresh data
            $stmt = $conn->prepare("SELECT * FROM infograph WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $infograph = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Error updating infographic.";
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
    margin-bottom: 6px;
    font-weight: 600;
    color: #333;
}
input {
    padding: 10px 12px;
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
.cover-preview {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fafafa;
    padding: 10px;
    max-height: 300px;
    overflow: hidden;
    margin-top: 10px;
}
.cover-preview img {
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
    <h2>✏️ Edit Infographic</h2>

    <?php 
    if ($error) echo "<p class='upload-message error'>$error</p>"; 
    if ($upload_msg) echo "<p class='upload-message success'>$upload_msg</p>"; 
    ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-layout">
            <div>
                <label>Title:</label>
                <input type="text" name="infographic_title" value="<?= htmlspecialchars($infograph['title']) ?>" required>

                <label>Date Added:</label>
                <input type="date" name="date_added" value="<?= htmlspecialchars($infograph['date']) ?>" required>

                <label>Theme:</label>
                <input type="text" name="infographic_theme" value="<?= htmlspecialchars($infograph['theme']) ?>" required>

                <label>Replace Image (optional):</label>
                <div class="hint">Current image shown on right →</div>
                <input type="file" name="infographic_image" id="infographicImage" accept="image/*">
                <div class="file-name" id="fileName"></div>
            </div>

            <div class="cover-preview">
                <?php if (!empty($infograph['image'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($infograph['image']) ?>" alt="Infographic" id="currentImage">
                <?php else: ?>
                    <p>No image uploaded.</p>
                <?php endif; ?>
            </div>

            <div class="button-wrapper">
                <button type="submit">💾 Save Changes</button>
                <br><br>
                <button type="button" onclick="window.location.href='admin_infograph.php'" style="background:#666; margin-top:10px; padding: 12px 25px; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    ← Back to Infographics
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// File name preview
document.getElementById('infographicImage').addEventListener('change', function(){
    document.getElementById('fileName').textContent = this.files.length ? "Selected: " + this.files[0].name : "";
});
</script>
</body>
</html>
