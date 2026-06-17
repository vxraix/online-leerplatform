<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    
    if ($course_id > 0) {
        if (isAdmin()) {
            // Admin can delete any enrollment
            $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $_SESSION['user_id'];
        } else {
            // Students can only delete their own enrollments
            $user_id = $_SESSION['user_id'];
        }
        
        $stmt = $pdo->prepare("DELETE FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
    }
}

header('Location: ' . (isset($_GET['redirect']) ? $_GET['redirect'] : 'my_courses.php'));
exit;


