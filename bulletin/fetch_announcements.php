<?php
include 'db_connect.php';

$mode = $_GET['mode'] ?? 'past';
// Use full datetime comparison so announcements move exactly when their date+time passes
// Queries
$past_query = "
    SELECT * FROM announcements
    WHERE date_posted <= NOW()
    ORDER BY date_posted DESC
";
$upcoming_query = "
    SELECT * FROM announcements
    WHERE date_posted > NOW()
    ORDER BY date_posted ASC
";

function categoryColor($cat) {
    switch ($cat) {
        case 'Policy Update': return 'policy';
        case 'New Features': return 'feature';
        case 'New Module': return 'module';
        default: return '';
    }
}

if ($mode === 'past' || $mode === 'all') {
    $past = $conn->query($past_query);

    if ($past && $past->num_rows > 0) {
        while ($row = $past->fetch_assoc()) {
            $title = htmlspecialchars($row['title']);
            $content = nl2br(htmlspecialchars($row['content']));
            $category = htmlspecialchars($row['category']);
            $categoryClass = categoryColor($row['category']);
            $date_posted = '';
            if (!empty($row['date_posted'])) {
                try { $dtt = new DateTime($row['date_posted']); $date_posted = htmlspecialchars($dtt->format('M j, Y \a\t g:i A')); } catch(Exception $e){ $date_posted = htmlspecialchars($row['date_posted']); }
            }

            echo "<div class='bulletin-card {$categoryClass}'>\n";
            echo "<div class='card-content'>\n";
            echo "<h3>{$title}</h3>\n";
            echo "<p>{$content}</p>\n";
            echo "<div class='card-footer'>\n";
            echo "<small><b>{$category}</b></small><br>\n";
            echo "<small><i>Posted: {$date_posted}</i></small>\n";
            echo "</div>\n";

            if (!empty($row['link'])) {
                $link = htmlspecialchars($row['link']);
                echo "<a href='{$link}' target='_blank' rel='noopener noreferrer' class='card-link'>View Link</a>\n";
            }

            if (!empty($row['attachment'])) {
                $attachmentPath = __DIR__ . DIRECTORY_SEPARATOR . $row['attachment'];
                if (file_exists($attachmentPath)) {
                    $attUrl = htmlspecialchars($row['attachment']);
                    echo "<a href='{$attUrl}' target='_blank' rel='noopener noreferrer' class='card-link'>Download Attachment</a>\n";
                }
            }
            echo "</div>\n</div>\n";
        }
    } else {
        if ($mode === 'past') echo '<p>No announcements available.</p>';
    }
}

if ($mode === 'upcoming' || $mode === 'all') {
    $upcoming = $conn->query($upcoming_query);

    if ($upcoming && $upcoming->num_rows > 0) {
        while ($row = $upcoming->fetch_assoc()) {
            $title = htmlspecialchars($row['title']);
            $categoryClass = categoryColor($row['category']);
            $date_posted = '';
            if (!empty($row['date_posted'])) {
                try { $dtt = new DateTime($row['date_posted']); $date_posted = htmlspecialchars($dtt->format('M j, Y \a\t g:i A')); } catch(Exception $e){ $date_posted = htmlspecialchars($row['date_posted']); }
            }

            echo "<div class='bulletin-card {$categoryClass}'>\n";
            echo "<div class='card-content'>\n";
            echo "<h3>{$title}</h3>\n";
            echo "<p><i>Scheduled on:</i> {$date_posted}</p>\n";
            echo "</div>\n</div>\n";
        }
    } else {
        if ($mode === 'upcoming') echo '<p>No upcoming events.</p>';
    }
}
?>
