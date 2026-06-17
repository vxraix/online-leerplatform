<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();

// Handle delete (must be before SELECT)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if (isAdmin()) {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$delId]);
    } elseif (isTeacher()) {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$delId, $_SESSION['user_id']]);
    }
    header('Location: courses.php');
    exit;
}

// Search and filter
$search = sanitize($_GET['search'] ?? '');
$active_filter = sanitize($_GET['active'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(title LIKE ? OR description LIKE ? OR instructor LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (isAdmin()) {
    if ($active_filter !== '') {
        $where[] = "is_active = ?";
        $params[] = $active_filter === '1' ? 1 : 0;
    }
} elseif (isTeacher()) {
    // Teachers see only their own courses (active + inactive)
    $where[] = "teacher_id = ?";
    $params[] = $_SESSION['user_id'];
    if ($active_filter !== '') {
        $where[] = "is_active = ?";
        $params[] = $active_filter === '1' ? 1 : 0;
    }
} else {
    // Students only see active courses
    $where[] = "is_active = 1";
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get courses
$sql = "SELECT id, title, description, instructor, teacher_id, duration_hours, price, is_active, created_at FROM courses $where_sql ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// For students: check which courses they're enrolled in
$enrolled_course_ids = [];
if (isStudent()) {
    $stmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $enrolled_course_ids = array_column($stmt->fetchAll(), 'course_id');
}

$pageTitle = 'Cursussen';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php if (isAdmin()): ?>
            <?php include 'includes/admin_sidebar.php'; ?>
        <?php elseif (isTeacher()): ?>
            <?php include 'includes/teacher_sidebar.php'; ?>
        <?php else: ?>
            <?php include 'includes/student_sidebar.php'; ?>
        <?php endif; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-book"></i> <?php echo isTeacher() ? 'Mijn Cursussen' : 'Cursussen'; ?></h1>
                <?php if (isAdmin() || isTeacher()): ?>
                    <a href="course_add.php" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Nieuwe Cursus
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Zoek op titel, beschrijving of instructeur..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <?php if (isAdmin() || isTeacher()): ?>
                            <div class="col-md-3">
                                <select class="form-select" name="active">
                                    <option value="">Alle status</option>
                                    <option value="1" <?php echo $active_filter === '1' ? 'selected' : ''; ?>>Actief</option>
                                    <option value="0" <?php echo $active_filter === '0' ? 'selected' : ''; ?>>Inactief</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-search"></i> Zoeken
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="courses.php" class="btn btn-secondary w-100">Reset</a>
                            </div>
                        <?php else: ?>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-search"></i> Zoeken
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="courses.php" class="btn btn-secondary w-100">Reset</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Courses Grid -->
            <div class="row">
                <?php if (empty($courses)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">Geen cursussen gevonden.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($courses as $course):
                        $ratingSum = getCourseRatingSummary((int)$course['id']);
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 <?php echo !$course['is_active'] ? 'border-warning' : ''; ?>" style="overflow:hidden;">
                                <a href="course_detail.php?id=<?php echo $course['id']; ?>" style="display:block;height:130px;background:<?php echo gradientFor($course['title']); ?>;position:relative;overflow:hidden;text-decoration:none;">
                                    <div style="position:absolute;inset:0;background:radial-gradient(ellipse at top right, rgba(255,255,255,.18), transparent 60%);"></div>
                                    <div style="position:absolute;bottom:14px;left:18px;right:18px;display:flex;justify-content:space-between;align-items:flex-end;">
                                        <i class="bi bi-book" style="color:#fff;font-size:2.2rem;opacity:.55;"></i>
                                        <?php if ($ratingSum['count'] > 0): ?>
                                            <span style="background:rgba(0,0,0,.4);color:#fff;padding:.25rem .6rem;border-radius:999px;font-size:.8rem;font-weight:600;backdrop-filter:blur(8px);">
                                                <i class="bi bi-star-fill" style="color:#ffd54a;"></i> <?php echo number_format($ratingSum['average'], 1, ',', ''); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$course['is_active']): ?>
                                        <span style="position:absolute;top:12px;left:14px;background:rgba(255,193,7,.95);color:#000;padding:.25rem .6rem;border-radius:8px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;"><i class="bi bi-exclamation-triangle"></i> Inactief</span>
                                    <?php endif; ?>
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)); ?>
                                        <?php echo strlen($course['description'] ?? '') > 100 ? '...' : ''; ?>
                                    </p>
                                    <p class="mb-1"><strong>Instructeur:</strong> <?php echo htmlspecialchars($course['instructor']); ?></p>
                                    <p class="mb-1"><strong>Duur:</strong> <?php echo $course['duration_hours']; ?> uur</p>
                                    <p class="mb-3"><strong>Prijs:</strong> €<?php echo number_format($course['price'], 2, ',', '.'); ?></p>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between">
                                        <a href="course_detail.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Details
                                        </a>
                                        <?php
                                        $canManage = isAdmin() || (isTeacher() && (int)$course['teacher_id'] === (int)$_SESSION['user_id']);
                                        ?>
                                        <?php if ($canManage): ?>
                                            <div>
                                                <a href="course_edit.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="courses.php?delete=<?php echo $course['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Weet je zeker dat je deze cursus wilt verwijderen?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        <?php elseif (isStudent()): ?>
                                            <?php if (in_array($course['id'], $enrolled_course_ids)): ?>
                                                <span class="badge bg-success">Ingeschreven</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php
include 'includes/footer.php'; 
?>

