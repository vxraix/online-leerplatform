<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, role, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stats = getUserStats($userId);

// Alle badges + welke je hebt
$allBadges = $pdo->query("SELECT * FROM badges ORDER BY id ASC")->fetchAll();
$ownedStmt = $pdo->prepare("SELECT badge_id, earned_at FROM user_badges WHERE user_id = ?");
$ownedStmt->execute([$userId]);
$ownedRows = $ownedStmt->fetchAll();
$owned = [];
foreach ($ownedRows as $row) { $owned[(int)$row['badge_id']] = $row['earned_at']; }

// Recente activiteit
$activity = getRecentActivity(25, $userId);

// Aantal cursussen / inschrijvingen / voltooide modules
$counts = [
    'enrollments' => 0,
    'modules_done' => 0,
    'courses_done' => 0,
    'reviews' => 0,
];
$counts['enrollments'] = (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$userId")->fetchColumn();
$counts['modules_done'] = (int)$pdo->query("SELECT COUNT(*) FROM module_completions WHERE user_id=$userId")->fetchColumn();
$counts['reviews'] = (int)$pdo->query("SELECT COUNT(*) FROM course_ratings WHERE user_id=$userId")->fetchColumn();

// Cursussen voltooid berekenen
$cdone = $pdo->prepare("
    SELECT c.id, c.title, c.duration_hours,
           (SELECT COUNT(*) FROM course_modules cm WHERE cm.course_id = c.id) AS total_mods,
           (SELECT COUNT(*) FROM module_completions mc INNER JOIN course_modules cm2 ON cm2.id = mc.module_id WHERE cm2.course_id = c.id AND mc.user_id = ?) AS done_mods
    FROM courses c
    INNER JOIN enrollments e ON e.course_id = c.id AND e.user_id = ?
    ORDER BY c.title
");
$cdone->execute([$userId, $userId]);
$courseRows = $cdone->fetchAll();
foreach ($courseRows as $cr) {
    if ((int)$cr['total_mods'] > 0 && (int)$cr['done_mods'] === (int)$cr['total_mods']) $counts['courses_done']++;
}

$pageTitle = 'Mijn profiel';
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

            <!-- Hero / profielkaart -->
            <div class="card mb-4" style="overflow:hidden;border:none;">
                <div style="background: <?php echo gradientFor($user['first_name'] . ' ' . $user['last_name']); ?>; padding:2.25rem; position:relative; overflow:hidden;">
                    <div style="position:absolute;inset:0;background:radial-gradient(ellipse at top right, rgba(255,255,255,.18), transparent 60%);pointer-events:none;"></div>
                    <div class="d-flex align-items-center gap-4 flex-wrap" style="position:relative;">
                        <?php echo avatarHtml($user['first_name'] . ' ' . $user['last_name'], 96); ?>
                        <div class="text-white flex-grow-1">
                            <h1 class="mb-1" style="font-weight:800;letter-spacing:-0.03em;">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </h1>
                            <p class="mb-2" style="opacity:.9;">
                                @<?php echo htmlspecialchars($user['username']); ?>
                                · <span class="badge" style="background:rgba(255,255,255,.2);"><?php echo htmlspecialchars(roleLabel($user['role'])); ?></span>
                                · Lid sinds <?php echo date('d-m-Y', strtotime($user['created_at'])); ?>
                            </p>
                            <?php if (isStudent()): ?>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div style="background:rgba(0,0,0,.25);padding:.6rem 1.1rem;border-radius:14px;backdrop-filter:blur(8px);">
                                        <div style="font-size:.7rem;opacity:.75;text-transform:uppercase;letter-spacing:.1em;">Level</div>
                                        <div style="font-size:1.6rem;font-weight:800;line-height:1;"><?php echo $stats['level']; ?></div>
                                    </div>
                                    <div style="background:rgba(0,0,0,.25);padding:.6rem 1.1rem;border-radius:14px;backdrop-filter:blur(8px);">
                                        <div style="font-size:.7rem;opacity:.75;text-transform:uppercase;letter-spacing:.1em;">XP</div>
                                        <div style="font-size:1.6rem;font-weight:800;line-height:1;"><?php echo $stats['xp']; ?></div>
                                    </div>
                                    <div style="background:rgba(0,0,0,.25);padding:.6rem 1.1rem;border-radius:14px;backdrop-filter:blur(8px);">
                                        <div style="font-size:.7rem;opacity:.75;text-transform:uppercase;letter-spacing:.1em;">Streak</div>
                                        <div style="font-size:1.6rem;font-weight:800;line-height:1;"><i class="bi bi-fire" style="color:#ffae00;"></i> <?php echo $stats['streak_days']; ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (isStudent()): ?>
                <div class="card-body" style="background:var(--bg-card-solid);">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Level <?php echo $stats['level']; ?></small>
                        <small class="text-muted"><?php echo $stats['xp_in_level']; ?> / <?php echo $stats['xp_in_level'] + $stats['xp_to_next']; ?> XP · Level <?php echo $stats['level'] + 1; ?></small>
                    </div>
                    <div class="progress" style="height:12px;background:rgba(255,255,255,.05);border-radius:12px;">
                        <div class="progress-bar" role="progressbar" style="width:<?php echo $stats['progress_pct']; ?>%;background:linear-gradient(90deg, var(--theme-blue), #7c00ff);border-radius:12px;"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (isStudent()): ?>
            <!-- Stats grid -->
            <div class="row mb-4">
                <div class="col-md-3 col-6 mb-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-bookmark-check fs-1 text-primary"></i>
                            <h3 class="mt-2 mb-0"><?php echo $counts['enrollments']; ?></h3>
                            <small class="text-muted">Inschrijvingen</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-check2-circle fs-1 text-primary"></i>
                            <h3 class="mt-2 mb-0"><?php echo $counts['modules_done']; ?></h3>
                            <small class="text-muted">Modules voltooid</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-mortarboard fs-1 text-primary"></i>
                            <h3 class="mt-2 mb-0"><?php echo $counts['courses_done']; ?></h3>
                            <small class="text-muted">Cursussen voltooid</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-chat-quote fs-1 text-primary"></i>
                            <h3 class="mt-2 mb-0"><?php echo $counts['reviews']; ?></h3>
                            <small class="text-muted">Reviews</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Badges -->
            <div class="card mb-4" id="badges">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-patch-check text-primary"></i> Badges (<?php echo count($owned); ?> / <?php echo count($allBadges); ?>)</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($allBadges as $b):
                            $earned = isset($owned[(int)$b['id']]);
                        ?>
                            <div class="col-md-3 col-6">
                                <div class="text-center p-3" style="background:rgba(255,255,255,.04);border:1px solid var(--border-dark);border-radius:18px;opacity:<?php echo $earned ? '1' : '.4'; ?>;<?php echo $earned ? 'box-shadow:0 0 30px rgba(0,46,244,.18);' : ''; ?>">
                                    <div style="width:64px;height:64px;border-radius:50%;margin:0 auto .5rem;display:flex;align-items:center;justify-content:center;background:<?php echo $earned ? 'linear-gradient(135deg, var(--theme-blue), #7c00ff)' : 'rgba(255,255,255,.05)'; ?>;font-size:1.7rem;color:#fff;<?php echo $earned ? 'box-shadow:0 8px 24px rgba(0,46,244,.4);' : ''; ?>">
                                        <i class="bi <?php echo htmlspecialchars($b['icon']); ?>"></i>
                                    </div>
                                    <div class="fw-600"><?php echo htmlspecialchars($b['name']); ?></div>
                                    <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($b['description']); ?></small>
                                    <?php if ($earned): ?>
                                        <small class="text-success d-block mt-2"><i class="bi bi-check-circle-fill"></i> Verdiend <?php echo date('d-m-Y', strtotime($owned[(int)$b['id']])); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted d-block mt-2"><i class="bi bi-lock"></i> Vergrendeld</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Activity -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-activity text-primary"></i> Recente activiteit</h5></div>
                <div class="card-body">
                    <?php if (empty($activity)): ?>
                        <p class="text-muted mb-0">Nog geen activiteit. Schrijf je in voor je eerste cursus!</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                        <?php foreach ($activity as $a): ?>
                            <li class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border-dark);">
                                <div style="width:36px;height:36px;border-radius:10px;background:rgba(0,46,244,.15);color:var(--theme-blue);display:flex;align-items:center;justify-content:center;">
                                    <i class="bi <?php echo htmlspecialchars($a['icon']); ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <?php if (!empty($a['link'])): ?>
                                        <a href="<?php echo htmlspecialchars($a['link']); ?>" style="color:#e8eaef;font-weight:500;">
                                            <?php echo htmlspecialchars($a['title']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($a['title']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo date('d-m-Y H:i', strtotime($a['created_at'])); ?></small>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
