<?php
require_once 'config/config.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];

if (isset($_GET['mark_all'])) {
    markNotificationsRead($userId);
    header('Location: notifications.php');
    exit;
}
if (isset($_GET['mark']) && is_numeric($_GET['mark'])) {
    markNotificationsRead($userId, (int)$_GET['mark']);
    header('Location: notifications.php');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$userId]);
$notifs = $stmt->fetchAll();

$pageTitle = 'Notificaties';
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
                <h1 class="h2"><i class="bi bi-bell-fill"></i> Notificaties</h1>
                <a href="notifications.php?mark_all=1" class="btn btn-secondary">
                    <i class="bi bi-check2-all"></i> Alles markeren als gelezen
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <?php if (empty($notifs)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bell-slash" style="font-size:3rem;opacity:.4;"></i>
                            <p class="mt-2 mb-0">Geen notificaties</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifs as $n): ?>
                            <div class="d-flex gap-3 align-items-start py-3" style="border-bottom:1px solid var(--border-dark); <?php echo !$n['is_read'] ? 'background:rgba(0,46,244,.04);' : ''; ?>">
                                <div style="width:44px;height:44px;border-radius:12px;background:rgba(0,46,244,.15);color:var(--theme-blue);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="bi <?php echo htmlspecialchars($n['icon']); ?>" style="font-size:1.2rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-600"><?php echo htmlspecialchars($n['title']); ?>
                                                <?php if (!$n['is_read']): ?>
                                                    <span class="badge bg-success ms-1">Nieuw</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($n['message'])): ?>
                                                <div class="text-muted" style="font-size:.9rem;"><?php echo htmlspecialchars($n['message']); ?></div>
                                            <?php endif; ?>
                                            <small class="text-muted"><?php echo date('d-m-Y H:i', strtotime($n['created_at'])); ?></small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <?php if (!empty($n['link'])): ?>
                                                <a href="<?php echo htmlspecialchars($n['link']); ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-arrow-right"></i> Bekijk
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!$n['is_read']): ?>
                                                <a href="notifications.php?mark=<?php echo $n['id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="bi bi-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
