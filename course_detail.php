<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header('Location: courses.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: courses.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

$is_enrolled = false;
if (isStudent()) {
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$userId, $id]);
    $is_enrolled = $stmt->fetch() !== false;
}

$has_active_subscription = false;
if (isStudent()) {
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())");
    $stmt->execute([$userId]);
    $has_active_subscription = $stmt->fetch() !== false;
}

$enrollment_count = 0;
if (isAdminOrTeacher()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM enrollments WHERE course_id = ?");
    $stmt->execute([$id]);
    $enrollment_count = (int)$stmt->fetch()['c'];
}

// Modules + voortgang
$modules = getCourseModules($id);
$completed_ids = $is_enrolled ? getCompletedModuleIds($userId, $id) : [];
$progress = $is_enrolled ? getCourseProgress($userId, $id) : ['total' => count($modules), 'done' => 0, 'pct' => 0, 'is_complete' => false];

// Beoordelingen
$ratingSummary = getCourseRatingSummary($id);
$myRating = $is_enrolled ? getUserCourseRating($userId, $id) : null;
$reviews = getCourseReviews($id, 20);

$canManageCourse = isAdmin() || (isTeacher() && (int)$course['teacher_id'] === $userId);

$pageTitle = $course['title'];
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
            <!-- Hero met thumbnail-gradient -->
            <div class="course-hero mb-4" style="background: <?php echo gradientFor($course['title']); ?>; border-radius:24px; padding:2rem 2.25rem; position:relative; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.4);">
                <div style="position:absolute;inset:0;background:radial-gradient(ellipse at top right, rgba(255,255,255,.15), transparent 60%);pointer-events:none;"></div>
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge" style="background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(8px);">
                                <i class="bi bi-book"></i> Cursus
                            </span>
                            <?php if (!$course['is_active']): ?>
                                <span class="badge bg-warning text-dark">Inactief</span>
                            <?php endif; ?>
                        </div>
                        <h1 class="text-white mb-1" style="font-weight:800;letter-spacing:-0.03em;"><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p class="text-white mb-0" style="opacity:.85;font-size:1.05rem;">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($course['instructor']); ?>
                            &nbsp;·&nbsp; <i class="bi bi-clock"></i> <?php echo (int)$course['duration_hours']; ?> uur
                            &nbsp;·&nbsp; <i class="bi bi-currency-euro"></i><?php echo number_format($course['price'], 2, ',', '.'); ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <?php if ($ratingSummary['count'] > 0): ?>
                            <div class="text-white" style="font-size:2rem;font-weight:800;">
                                <i class="bi bi-star-fill" style="color:#ffd54a;"></i> <?php echo number_format($ratingSummary['average'], 1, ',', ''); ?>
                            </div>
                            <small class="text-white" style="opacity:.75;"><?php echo $ratingSummary['count']; ?> beoordelingen</small>
                        <?php endif; ?>
                        <div class="mt-2">
                            <a href="courses.php" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2);">
                                <i class="bi bi-arrow-left"></i> Terug
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- Beschrijving -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-info-circle text-primary"></i> Over deze cursus</h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($course['description'] ?? '')); ?></p>
                        </div>
                    </div>

                    <!-- Modules -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-list-ol text-primary"></i> Inhoud (<?php echo count($modules); ?> modules)</h5>
                            <?php if ($canManageCourse): ?>
                                <a href="course_modules.php?course_id=<?php echo $id; ?>" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-gear"></i> Modules beheren
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($is_enrolled && count($modules) > 0): ?>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Voortgang</small>
                                    <small class="text-muted"><?php echo $progress['done']; ?> / <?php echo $progress['total']; ?> · <?php echo $progress['pct']; ?>%</small>
                                </div>
                                <div class="progress mb-3" style="height:10px;background:rgba(255,255,255,.05);border-radius:10px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress['pct']; ?>%;background:linear-gradient(90deg, var(--theme-blue), #7c00ff);border-radius:10px;"></div>
                                </div>
                                <?php if ($progress['is_complete']): ?>
                                    <div class="alert alert-success d-flex justify-content-between align-items-center mb-3">
                                        <div><i class="bi bi-mortarboard"></i> Je hebt deze cursus voltooid!</div>
                                        <a href="certificate.php?course_id=<?php echo $id; ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-award"></i> Bekijk certificaat
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (empty($modules)): ?>
                                <p class="text-muted mb-0">Nog geen modules voor deze cursus.</p>
                            <?php else: ?>
                                <div class="list-group">
                                <?php foreach ($modules as $i => $m): $done = in_array((int)$m['id'], $completed_ids, true); ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center" style="background:rgba(255,255,255,.03);border-color:var(--border-dark);color:#e8eaef;border-radius:14px;margin-bottom:8px;">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge bg-success" style="min-width:32px;">
                                                <?php echo $i + 1; ?>
                                            </span>
                                            <div>
                                                <div class="fw-600 <?php echo $done ? 'text-muted text-decoration-line-through' : ''; ?>">
                                                    <?php echo htmlspecialchars($m['title']); ?>
                                                </div>
                                                <?php if (!empty($m['description'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($m['description']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($is_enrolled): ?>
                                            <form method="POST" action="module_action.php" class="m-0">
                                                <input type="hidden" name="module_id" value="<?php echo $m['id']; ?>">
                                                <input type="hidden" name="course_id" value="<?php echo $id; ?>">
                                                <?php if ($done): ?>
                                                    <button type="submit" name="action" value="uncomplete" class="btn btn-sm btn-secondary" title="Markeer als niet voltooid">
                                                        <i class="bi bi-check-circle-fill text-success"></i> Voltooid
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="complete" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check"></i> Markeer voltooid
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php else: ?>
                                            <i class="bi bi-lock text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Beoordelingen -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-star text-primary"></i> Beoordelingen
                                <?php if ($ratingSummary['count'] > 0): ?>
                                    <small class="text-muted ms-2">
                                        <?php echo number_format($ratingSummary['average'], 1, ',', ''); ?>/5 ·
                                        <?php echo $ratingSummary['count']; ?> review<?php echo $ratingSummary['count'] === 1 ? '' : 's'; ?>
                                    </small>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($is_enrolled): ?>
                                <form method="POST" action="rate_course.php" class="mb-4" id="rateForm">
                                    <input type="hidden" name="course_id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="rating" id="rateValue" value="<?php echo $myRating ? (int)$myRating['rating'] : 0; ?>">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="me-2 text-muted">Jouw beoordeling:</span>
                                        <div class="lp-stars" data-current="<?php echo $myRating ? (int)$myRating['rating'] : 0; ?>">
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <button type="button" class="lp-star-btn" data-value="<?php echo $s; ?>" style="background:none;border:none;font-size:1.7rem;color:#444;padding:0 .15rem;cursor:pointer;transition:transform .15s;">
                                                    <i class="bi bi-star-fill"></i>
                                                </button>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <textarea name="review" class="form-control mb-2" rows="3" placeholder="Schrijf een korte review (optioneel)..."><?php echo htmlspecialchars($myRating['review'] ?? ''); ?></textarea>
                                    <button type="submit" class="btn btn-success btn-sm" id="rateSubmit" <?php echo $myRating ? '' : 'disabled'; ?>>
                                        <i class="bi bi-send"></i> <?php echo $myRating ? 'Bijwerken' : 'Indienen'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (empty($reviews)): ?>
                                <p class="text-muted mb-0">Nog geen beoordelingen.</p>
                            <?php else: ?>
                                <?php foreach ($reviews as $r): $name = $r['first_name'] . ' ' . $r['last_name']; ?>
                                    <div class="d-flex gap-3 mb-3 pb-3" style="border-bottom:1px solid var(--border-dark);">
                                        <div><?php echo avatarHtml($name, 42); ?></div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong><?php echo htmlspecialchars($name); ?></strong>
                                                <small class="text-muted"><?php echo date('d-m-Y', strtotime($r['created_at'])); ?></small>
                                            </div>
                                            <div style="color:#ffd54a;font-size:.95rem;letter-spacing:1px;">
                                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                                    <i class="bi bi-star<?php echo $s <= (int)$r['rating'] ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <?php if (!empty($r['review'])): ?>
                                                <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($r['review'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <?php if (isAdminOrTeacher()): ?>
                        <div class="card mb-4">
                            <div class="card-header"><h5 class="mb-0"><i class="bi bi-mortarboard"></i> Studenten</h5></div>
                            <div class="card-body text-center">
                                <h2 class="mb-0"><?php echo $enrollment_count; ?></h2>
                                <p class="text-muted mb-0">Aantal ingeschreven studenten</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isStudent()): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <?php if ($is_enrolled): ?>
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle"></i> Je bent ingeschreven voor deze cursus.
                                    </div>
                                    <form method="POST" action="enrollment_delete.php">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Weet je zeker dat je je wilt uitschrijven?')">
                                            <i class="bi bi-x-circle"></i> Uitschrijven
                                        </button>
                                    </form>
                                <?php elseif ($has_active_subscription && $course['is_active']): ?>
                                    <form method="POST" action="enroll_student.php">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-plus-circle"></i> Inschrijven (+<?php echo XP_PER_ENROLL; ?> XP)
                                        </button>
                                    </form>
                                <?php elseif (!$has_active_subscription): ?>
                                    <div class="alert alert-warning">
                                        Je hebt geen actief abonnement. Neem contact op met een beheerder.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        Deze cursus is momenteel niet beschikbaar.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header"><h5 class="mb-0"><i class="bi bi-info-circle"></i> Cursus details</h5></div>
                        <div class="card-body">
                            <table class="table mb-0">
                                <tr><th>Instructeur:</th><td><?php echo htmlspecialchars($course['instructor']); ?></td></tr>
                                <tr><th>Duur:</th><td><?php echo $course['duration_hours']; ?> uur</td></tr>
                                <tr><th>Prijs:</th><td>€<?php echo number_format($course['price'], 2, ',', '.'); ?></td></tr>
                                <tr><th>Status:</th><td>
                                    <?php if ($course['is_active']): ?>
                                        <span class="badge bg-success">Actief</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Inactief</span>
                                    <?php endif; ?>
                                </td></tr>
                                <tr><th>Aangemaakt:</th><td><?php echo date('d-m-Y', strtotime($course['created_at'])); ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
(function() {
    const wrap = document.querySelector('.lp-stars');
    if (!wrap) return;
    const buttons = wrap.querySelectorAll('.lp-star-btn');
    const input = document.getElementById('rateValue');
    const submitBtn = document.getElementById('rateSubmit');
    const setStars = (val, persist) => {
        buttons.forEach((b) => {
            const v = parseInt(b.dataset.value, 10);
            b.style.color = v <= val ? '#ffd54a' : '#3a3a44';
        });
        if (persist) input.value = val;
        if (val > 0) submitBtn.removeAttribute('disabled');
    };
    setStars(parseInt(wrap.dataset.current || '0', 10), false);
    buttons.forEach((b) => {
        b.addEventListener('mouseenter', () => setStars(parseInt(b.dataset.value, 10), false));
        b.addEventListener('click', () => setStars(parseInt(b.dataset.value, 10), true));
    });
    wrap.addEventListener('mouseleave', () => setStars(parseInt(input.value || '0', 10), false));
})();
</script>

<?php include 'includes/footer.php'; ?>
