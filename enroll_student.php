<?php
require_once 'config/config.php';
requireLogin();

if (isAdmin()) {
    header('Location: enrollment_add.php');
    exit;
}

$pdo = getDBConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $user_id = $_SESSION['user_id'];
    
    if ($course_id === 0) {
        $error = 'Ongeldige cursus.';
    } else {
        // Check if course exists and is active
        $stmt = $pdo->prepare("SELECT id, is_active FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();
        
        if (!$course) {
            $error = 'Cursus niet gevonden.';
        } elseif (!$course['is_active']) {
            $error = 'Deze cursus is niet actief.';
        } else {
            // Check if enrollment already exists
            $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$user_id, $course_id]);
            if ($stmt->fetch()) {
                $error = 'Je bent al ingeschreven voor deze cursus.';
            } else {
                // Check if user has active subscription
                $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())");
                $stmt->execute([$user_id]);
                if (!$stmt->fetch()) {
                    $error = 'Je hebt geen actief abonnement. Neem contact op met een beheerder.';
                } else {
                    // Create enrollment
                    $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
                    
                    if ($stmt->execute([$user_id, $course_id])) {
                        // Cursusinfo voor logging
                        $stmt2 = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
                        $stmt2->execute([$course_id]);
                        $courseTitle = $stmt2->fetchColumn() ?: 'cursus';

                        // XP + badges
                        awardXp((int)$user_id, XP_PER_ENROLL, 'enroll');
                        addActivity((int)$user_id, 'enrolled', "Schreef in voor \"$courseTitle\"", 'bi-bookmark-plus', 'course_detail.php?id=' . $course_id);

                        // Eerste inschrijving ooit?
                        $cnt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ?");
                        $cnt->execute([$user_id]);
                        $totalEnrolled = (int)$cnt->fetchColumn();
                        if ($totalEnrolled === 1) unlockBadge((int)$user_id, 'first_enroll');
                        if ($totalEnrolled >= 5)  unlockBadge((int)$user_id, 'five_courses');
                        if ($totalEnrolled >= 10) unlockBadge((int)$user_id, 'ten_courses');

                        $_SESSION['celebrate'] = [
                            'title' => "+ " . XP_PER_ENROLL . " XP",
                            'subtitle' => "Ingeschreven voor: $courseTitle",
                        ];

                        header('Location: course_detail.php?id=' . $course_id);
                        exit;
                    } else {
                        $error = 'Er is een fout opgetreden bij de inschrijving.';
                    }
                }
            }
        }
    }
}

// If we get here, there was an error
header('Location: courses.php?error=' . urlencode($error));
exit;


