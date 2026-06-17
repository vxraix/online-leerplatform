<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();
$userId = (int)$_SESSION['user_id'];

$stats = [];
$stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['courses'] = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$stats['active_courses'] = (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE is_active = 1")->fetchColumn();
$stats['active_subscriptions'] = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE is_active = 1")->fetchColumn();
$stats['enrollments'] = (int)$pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();

// ----- Student data -----
$my_courses = [];
$student_progress = [];
$student_stats = null;
if (isStudent()) {
    $stmt = $pdo->prepare("
        SELECT c.*, e.enrolled_at,
               (SELECT COUNT(*) FROM course_modules cm WHERE cm.course_id = c.id) AS total_modules,
               (SELECT COUNT(*) FROM module_completions mc INNER JOIN course_modules cm2 ON cm2.id = mc.module_id WHERE cm2.course_id = c.id AND mc.user_id = ?) AS done_modules
        FROM courses c
        INNER JOIN enrollments e ON c.id = e.course_id
        WHERE e.user_id = ? AND c.is_active = 1
        ORDER BY e.enrolled_at DESC
        LIMIT 6
    ");
    $stmt->execute([$userId, $userId]);
    $my_courses = $stmt->fetchAll();

    $student_stats = getUserStats($userId);
}

// ----- Teacher data -----
$teacher_stats = [];
$teacher_courses = [];
if (isTeacher()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE teacher_id = ?");
    $stmt->execute([$userId]);
    $teacher_stats['courses'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE teacher_id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $teacher_stats['active_courses'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments e INNER JOIN courses c ON c.id = e.course_id WHERE c.teacher_id = ?");
    $stmt->execute([$userId]);
    $teacher_stats['enrollments'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count
        FROM courses c WHERE c.teacher_id = ?
        ORDER BY c.created_at DESC LIMIT 5
    ");
    $stmt->execute([$userId]);
    $teacher_courses = $stmt->fetchAll();
}

// ----- Admin chart data: inschrijvingen per maand laatste 12 mnd -----
$chartLabels = [];
$chartData = [];
if (isAdmin()) {
    $rows = $pdo->query("
        SELECT DATE_FORMAT(enrolled_at, '%Y-%m') AS ym, COUNT(*) AS c
        FROM enrollments
        WHERE enrolled_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY ym ORDER BY ym ASC
    ")->fetchAll();
    $map = [];
    foreach ($rows as $r) { $map[$r['ym']] = (int)$r['c']; }
    for ($i = 11; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-$i months"));
        $chartLabels[] = date('M Y', strtotime($ym . '-01'));
        $chartData[] = $map[$ym] ?? 0;
    }

    // Top cursussen
    $topCourses = $pdo->query("
        SELECT c.title, COUNT(e.id) AS cnt
        FROM courses c LEFT JOIN enrollments e ON e.course_id = c.id
        GROUP BY c.id ORDER BY cnt DESC LIMIT 5
    ")->fetchAll();
}

// ----- Global activity feed (laatste 12 voor iedereen) -----
$feed = getRecentActivity(12);

$pageTitle = 'Dashboard';
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
            <div class="welcome-banner animate-in">
                <?php
                $name = $_SESSION['user_name'] ?? '';
                $first = trim($name) ? explode(' ', $name)[0] : '';
                $display = $first ?: ($_SESSION['username'] ?? '');
                ?>
                <h2 class="text-white mb-1"><i class="bi bi-stars text-primary me-2"></i>Welkom terug, <?php echo htmlspecialchars($display); ?>!</h2>
                <p>
                <?php
                if (isAdmin()) {
                    echo 'Hier is je overzicht van gebruikers, cursussen en abonnementen.';
                } elseif (isTeacher()) {
                    echo 'Hier is je overzicht van jouw cursussen en studenten.';
                } else {
                    echo 'Je voortgang, recente activiteit en cursussen op één plek.';
                }
                ?>
                </p>
            </div>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard</h1>
            </div>

            <?php if (isStudent()): ?>
                <!-- ========== STUDENT DASHBOARD ========== -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3 animate-in delay-1">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Mijn Cursussen</h6>
                                        <h2><?php echo count($my_courses); ?></h2>
                                    </div>
                                    <div class="align-self-center"><i class="bi bi-book fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3 animate-in delay-2">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Level</h6>
                                        <h2><?php echo $student_stats['level']; ?></h2>
                                    </div>
                                    <div class="align-self-center"><i class="bi bi-shield-fill-check fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3 animate-in delay-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">XP</h6>
                                        <h2><?php echo $student_stats['xp']; ?></h2>
                                    </div>
                                    <div class="align-self-center"><i class="bi bi-lightning-charge fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3 animate-in delay-3">
                        <div class="card text-white" style="background:linear-gradient(155deg, #ff7a00, #ff3d3d) !important;border:1px solid rgba(255,255,255,.12);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Streak</h6>
                                        <h2><?php echo $student_stats['streak_days']; ?> 🔥</h2>
                                    </div>
                                    <div class="align-self-center"><i class="bi bi-fire fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Level progress -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>Level <?php echo $student_stats['level']; ?></strong> <span class="text-muted">→ Level <?php echo $student_stats['level'] + 1; ?></span></div>
                            <small class="text-muted"><?php echo $student_stats['xp_to_next']; ?> XP te gaan</small>
                        </div>
                        <div class="progress" style="height:14px;background:rgba(255,255,255,.05);border-radius:12px;">
                            <div class="progress-bar" role="progressbar" style="width:<?php echo $student_stats['progress_pct']; ?>%;background:linear-gradient(90deg, var(--theme-blue), #7c00ff);border-radius:12px;"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-bookmark-star text-primary"></i> Mijn cursussen</h5>
                                <a href="courses.php" class="btn btn-sm btn-secondary">Alle cursussen</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($my_courses)): ?>
                                    <div class="text-center py-4">
                                        <p class="text-muted mb-3">Je bent nog niet ingeschreven voor cursussen.</p>
                                        <a href="courses.php" class="btn btn-success"><i class="bi bi-arrow-right"></i> Bekijk beschikbare cursussen</a>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                    <?php foreach ($my_courses as $c):
                                        $total = (int)$c['total_modules']; $done = (int)$c['done_modules'];
                                        $pct = $total > 0 ? (int)round(($done / $total) * 100) : 0;
                                    ?>
                                        <div class="col-md-6 mb-3">
                                            <a href="course_detail.php?id=<?php echo $c['id']; ?>" class="d-block text-decoration-none p-3" style="background:<?php echo gradientFor($c['title']); ?>;border-radius:16px;color:#fff;position:relative;overflow:hidden;box-shadow:0 8px 22px rgba(0,0,0,.25);">
                                                <div style="position:absolute;inset:0;background:radial-gradient(ellipse at top right, rgba(255,255,255,.15), transparent 60%);pointer-events:none;"></div>
                                                <div style="position:relative;">
                                                    <div class="fw-600 mb-2" style="font-size:1.05rem;"><?php echo htmlspecialchars($c['title']); ?></div>
                                                    <small style="opacity:.85;"><i class="bi bi-person"></i> <?php echo htmlspecialchars($c['instructor']); ?></small>
                                                    <div class="mt-2">
                                                        <div class="d-flex justify-content-between" style="font-size:.75rem;opacity:.85;">
                                                            <span>Voortgang</span>
                                                            <span><?php echo $done; ?>/<?php echo $total; ?> · <?php echo $pct; ?>%</span>
                                                        </div>
                                                        <div class="progress mt-1" style="height:6px;background:rgba(0,0,0,.25);border-radius:6px;">
                                                            <div class="progress-bar" style="width:<?php echo $pct; ?>%;background:#fff;border-radius:6px;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header"><h5 class="mb-0"><i class="bi bi-activity text-primary"></i> Activiteit</h5></div>
                            <div class="card-body" style="max-height:520px;overflow-y:auto;">
                                <?php if (empty($feed)): ?>
                                    <p class="text-muted mb-0">Nog geen activiteit.</p>
                                <?php else: ?>
                                    <?php foreach ($feed as $a): $aname = $a['first_name'] . ' ' . $a['last_name']; ?>
                                        <div class="d-flex gap-2 py-2" style="border-bottom:1px solid var(--border-dark);">
                                            <?php echo avatarHtml($aname, 32); ?>
                                            <div class="flex-grow-1" style="min-width:0;font-size:.85rem;">
                                                <div><strong><?php echo htmlspecialchars($aname); ?></strong></div>
                                                <?php if (!empty($a['link'])): ?>
                                                    <a href="<?php echo htmlspecialchars($a['link']); ?>" class="text-muted">
                                                        <i class="bi <?php echo htmlspecialchars($a['icon']); ?>"></i> <?php echo htmlspecialchars($a['title']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="bi <?php echo htmlspecialchars($a['icon']); ?>"></i> <?php echo htmlspecialchars($a['title']); ?></span>
                                                <?php endif; ?>
                                                <div class="text-muted" style="font-size:.7rem;"><?php echo date('d-m H:i', strtotime($a['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif (isTeacher()): ?>
                <!-- ========== TEACHER DASHBOARD ========== -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3 animate-in delay-1">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div><h6 class="card-title">Mijn Cursussen</h6><h2><?php echo $teacher_stats['courses']; ?></h2></div>
                                    <div class="align-self-center"><i class="bi bi-book fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 animate-in delay-2">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div><h6 class="card-title">Actieve Cursussen</h6><h2><?php echo $teacher_stats['active_courses']; ?></h2></div>
                                    <div class="align-self-center"><i class="bi bi-check-circle fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 animate-in delay-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div><h6 class="card-title">Mijn Studenten</h6><h2><?php echo $teacher_stats['enrollments']; ?></h2></div>
                                    <div class="align-self-center"><i class="bi bi-mortarboard fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recente cursussen</h5>
                                <a href="course_add.php" class="btn btn-sm btn-success"><i class="bi bi-plus-circle"></i> Nieuwe Cursus</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($teacher_courses)): ?>
                                    <p class="text-muted mb-3">Je hebt nog geen cursussen aangemaakt.</p>
                                    <a href="course_add.php" class="btn btn-success">Cursus aanmaken</a>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr><th>Titel</th><th>Status</th><th>Studenten</th><th>Acties</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($teacher_courses as $c): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($c['title']); ?></td>
                                                        <td>
                                                            <?php if ($c['is_active']): ?>
                                                                <span class="badge bg-success">Actief</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Inactief</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo (int)$c['student_count']; ?></td>
                                                        <td>
                                                            <a href="course_detail.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                                            <a href="course_edit.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                                            <a href="course_modules.php?course_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-secondary"><i class="bi bi-list-ol"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header"><h5 class="mb-0"><i class="bi bi-activity text-primary"></i> Activiteit</h5></div>
                            <div class="card-body" style="max-height:520px;overflow-y:auto;">
                                <?php if (empty($feed)): ?>
                                    <p class="text-muted mb-0">Nog geen activiteit.</p>
                                <?php else: ?>
                                    <?php foreach ($feed as $a): $aname = $a['first_name'] . ' ' . $a['last_name']; ?>
                                        <div class="d-flex gap-2 py-2" style="border-bottom:1px solid var(--border-dark);">
                                            <?php echo avatarHtml($aname, 32); ?>
                                            <div class="flex-grow-1" style="min-width:0;font-size:.85rem;">
                                                <div><strong><?php echo htmlspecialchars($aname); ?></strong></div>
                                                <span class="text-muted"><i class="bi <?php echo htmlspecialchars($a['icon']); ?>"></i> <?php echo htmlspecialchars($a['title']); ?></span>
                                                <div class="text-muted" style="font-size:.7rem;"><?php echo date('d-m H:i', strtotime($a['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- ========== ADMIN DASHBOARD ========== -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3 animate-in delay-1">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div><h6 class="card-title">Gebruikers</h6><h2><?php echo $stats['users']; ?></h2></div>
                                    <div class="align-self-center"><i class="bi bi-people fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3 animate-in delay-2">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div><h6 class="card-title">Cursussen</h6><h2><?php echo $stats['courses']; ?></h2></div>
                                    <div class="align-self-center"><i class="bi bi-book fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3 animate-in delay-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div><h6 class="card-title">Inschrijvingen</h6><h2><?php echo $stats['enrollments']; ?></h2></div>
                                    <div class="align-self-center"><i class="bi bi-clipboard-check fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3 animate-in delay-3">
                        <div class="card text-white" style="background:linear-gradient(155deg, #7c00ff, #002ef4) !important;border:1px solid rgba(255,255,255,.12);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div><h6 class="card-title">Abonnementen</h6><h2><?php echo $stats['active_subscriptions']; ?></h2></div>
                                    <div class="align-self-center"><i class="bi bi-credit-card fs-1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header"><h5 class="mb-0"><i class="bi bi-graph-up-arrow text-primary"></i> Inschrijvingen — laatste 12 maanden</h5></div>
                            <div class="card-body">
                                <canvas id="enrollmentsChart" height="120"></canvas>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header"><h5 class="mb-0"><i class="bi bi-trophy text-primary"></i> Top cursussen</h5></div>
                            <div class="card-body">
                                <?php if (empty($topCourses)): ?>
                                    <p class="text-muted mb-0">Nog geen data.</p>
                                <?php else: ?>
                                    <?php $maxCnt = max(1, max(array_column($topCourses, 'cnt'))); foreach ($topCourses as $i => $tc): ?>
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <div style="width:30px;text-align:center;font-weight:800;font-size:1.1rem;color:var(--theme-blue);">#<?php echo $i + 1; ?></div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between">
                                                    <span><?php echo htmlspecialchars($tc['title']); ?></span>
                                                    <small class="text-muted"><?php echo $tc['cnt']; ?> inschrijvingen</small>
                                                </div>
                                                <div class="progress" style="height:6px;background:rgba(255,255,255,.05);border-radius:6px;">
                                                    <div class="progress-bar" style="width:<?php echo ($tc['cnt'] / $maxCnt) * 100; ?>%;background:linear-gradient(90deg, var(--theme-blue), #7c00ff);border-radius:6px;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header"><h5 class="mb-0"><i class="bi bi-activity text-primary"></i> Live activiteit</h5></div>
                            <div class="card-body" style="max-height:680px;overflow-y:auto;">
                                <?php if (empty($feed)): ?>
                                    <p class="text-muted mb-0">Nog geen activiteit.</p>
                                <?php else: ?>
                                    <?php foreach ($feed as $a): $aname = $a['first_name'] . ' ' . $a['last_name']; ?>
                                        <div class="d-flex gap-2 py-2" style="border-bottom:1px solid var(--border-dark);">
                                            <?php echo avatarHtml($aname, 32); ?>
                                            <div class="flex-grow-1" style="min-width:0;font-size:.85rem;">
                                                <div><strong><?php echo htmlspecialchars($aname); ?></strong></div>
                                                <span class="text-muted"><i class="bi <?php echo htmlspecialchars($a['icon']); ?>"></i> <?php echo htmlspecialchars($a['title']); ?></span>
                                                <div class="text-muted" style="font-size:.7rem;"><?php echo date('d-m H:i', strtotime($a['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
                <script>
                (function(){
                    const ctx = document.getElementById('enrollmentsChart');
                    if (!ctx) return;
                    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
                    gradient.addColorStop(0, 'rgba(0,46,244,0.55)');
                    gradient.addColorStop(1, 'rgba(0,46,244,0.02)');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($chartLabels); ?>,
                            datasets: [{
                                label: 'Inschrijvingen',
                                data: <?php echo json_encode($chartData); ?>,
                                borderColor: '#5a8aff',
                                backgroundColor: gradient,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#5a8aff',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                borderWidth: 2.5,
                            }]
                        },
                        options: {
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { ticks: { color: '#8890a6' }, grid: { color: 'rgba(255,255,255,0.04)' } },
                                y: { ticks: { color: '#8890a6', precision: 0 }, grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true },
                            },
                            responsive: true,
                            maintainAspectRatio: true,
                        }
                    });
                })();
                </script>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
