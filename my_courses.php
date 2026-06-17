<?php
require_once 'config/config.php';
requireLogin();

if (isAdmin()) {
    header('Location: enrollments.php');
    exit;
}

$pdo = getDBConnection();

// Get user's enrollments
$stmt = $pdo->prepare("
    SELECT c.*, e.enrolled_at, e.id as enrollment_id 
    FROM courses c
    INNER JOIN enrollments e ON c.id = e.course_id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$my_courses = $stmt->fetchAll();

$pageTitle = 'Mijn Cursussen';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/student_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-list-check"></i> Mijn Cursussen</h1>
                <a href="courses.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nieuwe Cursus Zoeken
                </a>
            </div>
            
            <?php if (empty($my_courses)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Je bent nog niet ingeschreven voor cursussen.
                    <a href="courses.php" class="alert-link">Bekijk beschikbare cursussen</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($my_courses as $course): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>
                                        <?php echo strlen($course['description']) > 100 ? '...' : ''; ?>
                                    </p>
                                    <p class="mb-1"><strong>Instructeur:</strong> <?php echo htmlspecialchars($course['instructor']); ?></p>
                                    <p class="mb-1"><strong>Duur:</strong> <?php echo $course['duration_hours']; ?> uur</p>
                                    <p class="mb-1"><strong>Prijs:</strong> €<?php echo number_format($course['price'], 2, ',', '.'); ?></p>
                                    <p class="mb-3 text-muted">
                                        <small>Ingeschreven: <?php echo date('d-m-Y', strtotime($course['enrolled_at'])); ?></small>
                                    </p>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between">
                                        <a href="course_detail.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Details
                                        </a>
                                        <form method="POST" action="enrollment_delete.php" class="d-inline">
                                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Weet je zeker dat je je wilt uitschrijven?')">
                                                <i class="bi bi-x-circle"></i> Uitschrijven
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

