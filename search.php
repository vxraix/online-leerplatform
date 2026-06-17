<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();
$q = trim((string)($_GET['q'] ?? ''));
$results = ['courses' => [], 'docenten' => [], 'gebruikers' => []];

if ($q !== '') {
    $like = '%' . $q . '%';

    // Cursussen
    if (isAdmin()) {
        $stmt = $pdo->prepare("SELECT id, title, description, instructor, is_active FROM courses WHERE title LIKE ? OR description LIKE ? OR instructor LIKE ? ORDER BY title LIMIT 25");
        $stmt->execute([$like, $like, $like]);
    } elseif (isTeacher()) {
        $stmt = $pdo->prepare("SELECT id, title, description, instructor, is_active FROM courses WHERE (teacher_id = ? OR is_active = 1) AND (title LIKE ? OR description LIKE ? OR instructor LIKE ?) ORDER BY title LIMIT 25");
        $stmt->execute([$_SESSION['user_id'], $like, $like, $like]);
    } else {
        $stmt = $pdo->prepare("SELECT id, title, description, instructor, is_active FROM courses WHERE is_active = 1 AND (title LIKE ? OR description LIKE ? OR instructor LIKE ?) ORDER BY title LIMIT 25");
        $stmt->execute([$like, $like, $like]);
    }
    $results['courses'] = $stmt->fetchAll();

    // Docenten — voor iedereen zichtbaar (publieke info)
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, username FROM users WHERE role = 'docent' AND (first_name LIKE ? OR last_name LIKE ? OR username LIKE ?) LIMIT 15");
    $stmt->execute([$like, $like, $like]);
    $results['docenten'] = $stmt->fetchAll();

    // Alle gebruikers (alleen admin)
    if (isAdmin()) {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, username, email, role FROM users WHERE first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ? LIMIT 20");
        $stmt->execute([$like, $like, $like, $like]);
        $results['gebruikers'] = $stmt->fetchAll();
    }
}

$totalResults = count($results['courses']) + count($results['docenten']) + count($results['gebruikers']);

$pageTitle = 'Zoekresultaten';
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
                <h1 class="h2"><i class="bi bi-search"></i> Zoekresultaten</h1>
            </div>

            <?php if ($q === ''): ?>
                <div class="alert alert-info">Typ een zoekterm in de bovenbalk om te beginnen.</div>
            <?php else: ?>
                <p class="text-muted mb-4"><?php echo $totalResults; ?> resultaten voor <strong>"<?php echo htmlspecialchars($q); ?>"</strong></p>

                <?php if (!empty($results['courses'])): ?>
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0"><i class="bi bi-book text-primary"></i> Cursussen (<?php echo count($results['courses']); ?>)</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($results['courses'] as $c): ?>
                                    <div class="col-md-6 mb-3">
                                        <a href="course_detail.php?id=<?php echo $c['id']; ?>" class="d-block p-3 text-decoration-none" style="background:rgba(255,255,255,.03);border:1px solid var(--border-dark);border-radius:14px;color:#e8eaef;transition:all .2s;">
                                            <div class="fw-600 mb-1"><?php echo htmlspecialchars($c['title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($c['instructor']); ?></small>
                                            <p class="text-muted mb-0 mt-1" style="font-size:.85rem;"><?php echo htmlspecialchars(mb_substr($c['description'] ?? '', 0, 100)); ?>...</p>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($results['docenten'])): ?>
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0"><i class="bi bi-person-badge text-primary"></i> Docenten (<?php echo count($results['docenten']); ?>)</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($results['docenten'] as $d): $name = $d['first_name'] . ' ' . $d['last_name']; ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="d-flex align-items-center gap-3 p-3" style="background:rgba(255,255,255,.03);border:1px solid var(--border-dark);border-radius:14px;">
                                            <?php echo avatarHtml($name, 44); ?>
                                            <div>
                                                <div class="fw-600"><?php echo htmlspecialchars($name); ?></div>
                                                <small class="text-muted">@<?php echo htmlspecialchars($d['username']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($results['gebruikers'])): ?>
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0"><i class="bi bi-people text-primary"></i> Gebruikers (<?php echo count($results['gebruikers']); ?>)</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($results['gebruikers'] as $u): $name = $u['first_name'] . ' ' . $u['last_name']; ?>
                                    <div class="col-md-4 mb-3">
                                        <a href="user_edit.php?id=<?php echo $u['id']; ?>" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="background:rgba(255,255,255,.03);border:1px solid var(--border-dark);border-radius:14px;color:#e8eaef;">
                                            <?php echo avatarHtml($name, 44); ?>
                                            <div>
                                                <div class="fw-600"><?php echo htmlspecialchars($name); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($u['email']); ?> · <?php echo htmlspecialchars(roleLabel($u['role'])); ?></small>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($totalResults === 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-search"></i> Geen resultaten gevonden voor "<?php echo htmlspecialchars($q); ?>".
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
