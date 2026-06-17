<?php
require_once 'config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: courses.php'); exit; }

$courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
$rating   = isset($_POST['rating'])    ? (int)$_POST['rating']    : 0;
$review   = trim((string)($_POST['review'] ?? ''));
$review   = mb_substr($review, 0, 1000);

if ($courseId > 0 && $rating >= 1 && $rating <= 5) {
    saveCourseRating((int)$_SESSION['user_id'], $courseId, $rating, $review);
}

header('Location: course_detail.php?id=' . $courseId);
exit;
