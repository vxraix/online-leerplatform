<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();

$action    = $_POST['action']    ?? '';
$moduleId  = isset($_POST['module_id'])  ? (int)$_POST['module_id']  : 0;
$courseId  = isset($_POST['course_id'])  ? (int)$_POST['course_id']  : 0;
$userId    = (int)$_SESSION['user_id'];

$redirect = $courseId > 0 ? 'course_detail.php?id=' . $courseId : 'courses.php';

if ($moduleId === 0) {
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'complete') {
    // Alleen ingeschreven studenten mogen voltooien
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.course_id
        FROM course_modules cm
        INNER JOIN enrollments e ON e.course_id = cm.course_id AND e.user_id = ?
        WHERE cm.id = ?
    ");
    $stmt->execute([$userId, $moduleId]);
    if ($stmt->fetch()) {
        $res = completeModule($userId, $moduleId);
        if ($res['ok'] && !empty($res['newly_done'])) {
            $progress = $res['progress'];
            $_SESSION['celebrate'] = [
                'title'    => '+ ' . XP_PER_MODULE . ' XP',
                'subtitle' => "Module voltooid · {$progress['done']}/{$progress['total']}",
            ];
        }
    }
} elseif ($action === 'uncomplete') {
    uncompleteModule($userId, $moduleId);
}

header('Location: ' . $redirect);
exit;
