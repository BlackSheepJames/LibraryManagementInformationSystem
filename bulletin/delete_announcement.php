<?php
include 'db_connect.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['status'=>'error','message'=>'Invalid id']);
    exit;
}

$sel = $conn->prepare('SELECT attachment FROM announcements WHERE id = ? LIMIT 1');
$sel->bind_param('i', $id);
$sel->execute();
$res = $sel->get_result();
$attachment = '';
if ($row = $res->fetch_assoc()) $attachment = $row['attachment'];
$sel->close();

$del = $conn->prepare('DELETE FROM announcements WHERE id = ?');
$del->bind_param('i', $id);
if ($del->execute()) {
    if (!empty($attachment)) {
        $path = __DIR__ . DIRECTORY_SEPARATOR . $attachment;
        if (file_exists($path)) @unlink($path);
    }
    echo json_encode(['status'=>'ok','message'=>'Deleted','id'=>$id]);
} else {
    echo json_encode(['status'=>'error','message'=>'Failed to delete']);
}
$del->close();
?>
