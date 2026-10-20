<?php
include 'db_connect.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? 'add';
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$category = trim($_POST['category'] ?? '');
$link = trim($_POST['link'] ?? '');
$date_posted_input = trim($_POST['date_posted'] ?? date('Y-m-d\TH:i'));

// parse input into DateTime then format for DB and for human display
$dt = DateTime::createFromFormat('Y-m-d\TH:i', $date_posted_input);
if (!$dt) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date_posted_input);
}
if (!$dt) { $dt = new DateTime(); }
$date_posted_db = $dt->format('Y-m-d H:i:s');
$date_posted_friendly = $dt->format('M j, Y g:i A');

if ($title === '' || $content === '' || $category === '') {
    echo json_encode(['status'=>'error','message'=>'Title, content and category required']);
    exit;
}

$attachmentPath = '';
// handle file upload
if (!empty($_FILES['attachment']['name'])) {
    $targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $originalName = basename($_FILES['attachment']['name']);
    $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
    $targetFile = $targetDir . time() . '_' . $safe;

    $allowedMime = [
        'application/pdf', 'image/png', 'image/jpeg', 'image/gif',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $_FILES['attachment']['tmp_name']) : '';
    if ($finfo) finfo_close($finfo);
    if ($mime && !in_array($mime, $allowedMime)) {
        echo json_encode(['status'=>'error','message'=>'Invalid attachment type']);
        exit;
    }
    if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
        echo json_encode(['status'=>'error','message'=>'Failed to save uploaded file']);
        exit;
    }
    $attachmentPath = 'uploads/' . basename($targetFile);
}

if ($action === 'update' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    // get existing attachment
    $sel = $conn->prepare('SELECT attachment FROM announcements WHERE id = ? LIMIT 1');
    $sel->bind_param('i', $id);
    $sel->execute();
    $res = $sel->get_result();
    $oldAttach = '';
    if ($r = $res->fetch_assoc()) $oldAttach = $r['attachment'];
    $sel->close();

    // if no new attachment uploaded, keep old
    if ($attachmentPath === '') $attachmentPath = $oldAttach;

    $up = $conn->prepare('UPDATE announcements SET title = ?, content = ?, category = ?, link = ?, attachment = ?, date_posted = ? WHERE id = ?');
    if (!$up) { echo json_encode(['status'=>'error','message'=>'DB prepare failed']); exit; }
    $up->bind_param('ssssssi', $title, $content, $category, $link, $attachmentPath, $date_posted_db, $id);
    if ($up->execute()) {
        // if new attachment replaced old, delete old file
        if (!empty($attachmentPath) && $attachmentPath !== $oldAttach && !empty($oldAttach)) {
            $oldPath = __DIR__ . DIRECTORY_SEPARATOR . $oldAttach;
            if (file_exists($oldPath)) @unlink($oldPath);
        }
    echo json_encode(['status'=>'ok','action'=>'update','id'=>$id,'title'=>$title,'date_posted'=>$date_posted_friendly,'category'=>$category]);
    } else {
        echo json_encode(['status'=>'error','message'=>'Update failed']);
    }
    $up->close();
    exit;
}

// default: add
// default: add
$stmt = $conn->prepare('INSERT INTO announcements (title, content, category, link, attachment, date_posted) VALUES (?, ?, ?, ?, ?, ?)');
if (!$stmt) { echo json_encode(['status'=>'error','message'=>'DB prepare failed']); exit; }
$stmt->bind_param('ssssss', $title, $content, $category, $link, $attachmentPath, $date_posted_db);
if ($stmt->execute()) {
    $id = $conn->insert_id;
    echo json_encode(['status'=>'ok','action'=>'add','id'=>$id,'title'=>$title,'date_posted'=>$date_posted_friendly,'category'=>$category]);
} else {
    echo json_encode(['status'=>'error','message'=>'Insert failed']);
}
$stmt->close();
?>
