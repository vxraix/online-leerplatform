<?php
require_once 'config/config.php';
requireAdmin();

$pdo = getDBConnection();
$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'Inschrijving succesvol verwijderd.';
    } else {
        $error = 'Fout bij verwijderen van inschrijving.';
    }
}

// Search and filter
$search = sanitize($_GET['search'] ?? '');
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$course_filter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR c.title LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($user_filter > 0) {
    $where[] = "e.user_id = ?";
    $params[] = $user_filter;
}

if ($course_filter > 0) {
    $where[] = "e.course_id = ?";
    $params[] = $course_filter;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get enrollments with user and course info
$sql = "SELECT e.*, u.username, u.email, u.first_name, u.last_name, c.title as course_title, c.instructor 
        FROM enrollments e 
        INNER JOIN users u ON e.user_id = u.id 
        INNER JOIN courses c ON e.course_id = c.id 
        $where_sql 
        ORDER BY e.enrolled_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// Get users and courses for filters
$stmt = $pdo->query("SELECT id, username, first_name, last_name FROM users ORDER BY username");
$users = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, title FROM courses ORDER BY title");
$courses = $stmt->fetchAll();

$pageTitle = 'Inschrijvingen';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-clipboard-check"></i> Inschrijvingen</h1>
                <a href="enrollment_add.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nieuwe Inschrijving
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Zoek op gebruiker of cursus..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="user_id">
                                <option value="">Alle gebruikers</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="course_id">
                                <option value="">Alle cursussen</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>" <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="enrollments.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Enrollments Table -->
            <div class="card">
                <div class="card-header">
                    <h5>Inschrijvingen (<?php echo count($enrollments); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Gebruiker</th>
                                    <th>Cursus</th>
                                    <th>Instructeur</th>
                                    <th>Ingeschreven Op</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($enrollments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Geen inschrijvingen gevonden.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?php echo $enrollment['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($enrollment['username']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['instructor']); ?></td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($enrollment['enrolled_at'])); ?></td>
                                            <td>
                                                <a href="enrollments.php?delete=<?php echo $enrollment['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Weet je zeker dat je deze inschrijving wilt verwijderen?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

