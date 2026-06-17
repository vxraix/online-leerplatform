<?php
require_once 'config/config.php';
requireTeacher();

$pdo = getDBConnection();

$search = sanitize($_GET['search'] ?? '');
$course_filter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

$where = ['c.teacher_id = ?'];
$params = [$_SESSION['user_id']];

if ($search !== '') {
    $where[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}

if ($course_filter > 0) {
    $where[] = 'c.id = ?';
    $params[] = $course_filter;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT u.id AS user_id, u.username, u.email, u.first_name, u.last_name,
           c.id AS course_id, c.title AS course_title,
           e.id AS enrollment_id, e.enrolled_at
    FROM enrollments e
    INNER JOIN users u ON u.id = e.user_id
    INNER JOIN courses c ON c.id = e.course_id
    $where_sql
    ORDER BY e.enrolled_at DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Cursusfilter-opties: eigen cursussen
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ? ORDER BY title");
$stmt->execute([$_SESSION['user_id']]);
$myCourses = $stmt->fetchAll();

$pageTitle = 'Mijn Studenten';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/teacher_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-mortarboard"></i> Mijn Studenten</h1>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="search" placeholder="Zoek op naam of e-mail..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="course_id">
                                <option value="">Alle cursussen</option>
                                <?php foreach ($myCourses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $course_filter === (int)$c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-search"></i> Zoeken</button>
                        </div>
                        <div class="col-md-1">
                            <a href="my_students.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Studenten (<?php echo count($rows); ?>)</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Naam</th>
                                    <th>Gebruikersnaam</th>
                                    <th>E-mail</th>
                                    <th>Cursus</th>
                                    <th>Ingeschreven op</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">Geen ingeschreven studenten gevonden.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($r['username']); ?></td>
                                            <td><?php echo htmlspecialchars($r['email']); ?></td>
                                            <td>
                                                <a href="course_detail.php?id=<?php echo $r['course_id']; ?>">
                                                    <?php echo htmlspecialchars($r['course_title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($r['enrolled_at'])); ?></td>
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
