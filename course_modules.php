<?php
require_once 'config/config.php';
requireAdminOrTeacher();

$pdo = getDBConnection();
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($courseId === 0) { header('Location: courses.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course) { header('Location: courses.php'); exit; }

// Docent mag alleen eigen cursus
if (isTeacher() && (int)$course['teacher_id'] !== (int)$_SESSION['user_id']) {
    header('Location: courses.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        if ($title === '') {
            $error = 'Titel is verplicht.';
        } else {
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(position),0)+1 FROM course_modules WHERE course_id = ?");
            $stmt->execute([$courseId]);
            $pos = (int)$stmt->fetchColumn();
            $pdo->prepare("INSERT INTO course_modules (course_id, title, description, position) VALUES (?, ?, ?, ?)")
                ->execute([$courseId, $title, $description, $pos]);
            $success = 'Module toegevoegd.';
        }
    } elseif ($action === 'delete') {
        $mid = (int)($_POST['module_id'] ?? 0);
        $pdo->prepare("DELETE FROM course_modules WHERE id = ? AND course_id = ?")->execute([$mid, $courseId]);
        $success = 'Module verwijderd.';
    } elseif ($action === 'update') {
        $mid = (int)($_POST['module_id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        if ($title === '') {
            $error = 'Titel is verplicht.';
        } else {
            $pdo->prepare("UPDATE course_modules SET title = ?, description = ? WHERE id = ? AND course_id = ?")
                ->execute([$title, $description, $mid, $courseId]);
            $success = 'Module bijgewerkt.';
        }
    } elseif ($action === 'reorder') {
        $up = (int)($_POST['module_id'] ?? 0);
        $dir = $_POST['dir'] ?? '';
        $modules = getCourseModules($courseId);
        $idx = null;
        foreach ($modules as $i => $m) if ((int)$m['id'] === $up) { $idx = $i; break; }
        if ($idx !== null) {
            $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
            if ($swap >= 0 && $swap < count($modules)) {
                $a = $modules[$idx]; $b = $modules[$swap];
                $pdo->prepare("UPDATE course_modules SET position = ? WHERE id = ?")->execute([$b['position'], $a['id']]);
                $pdo->prepare("UPDATE course_modules SET position = ? WHERE id = ?")->execute([$a['position'], $b['id']]);
            }
        }
    }
}

$modules = getCourseModules($courseId);
$pageTitle = 'Modules — ' . $course['title'];
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php if (isAdmin()): ?>
            <?php include 'includes/admin_sidebar.php'; ?>
        <?php else: ?>
            <?php include 'includes/teacher_sidebar.php'; ?>
        <?php endif; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-list-ol"></i> Modules — <?php echo htmlspecialchars($course['title']); ?></h1>
                <a href="course_detail.php?id=<?php echo $courseId; ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Terug
                </a>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nieuwe module</h5></div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add">
                        <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="Titel" required></div>
                        <div class="col-md-6"><input type="text" name="description" class="form-control" placeholder="Beschrijving (optioneel)"></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-success w-100"><i class="bi bi-plus"></i> Toevoegen</button></div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-collection"></i> Modules (<?php echo count($modules); ?>)</h5></div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <p class="text-muted mb-0">Nog geen modules. Voeg er hierboven een toe.</p>
                    <?php else: ?>
                        <?php foreach ($modules as $i => $m): ?>
                            <form method="POST" class="border rounded p-3 mb-3" style="border-color:var(--border-dark) !important;">
                                <input type="hidden" name="module_id" value="<?php echo $m['id']; ?>">
                                <input type="hidden" name="action" value="update">
                                <div class="row g-2 align-items-center">
                                    <div class="col-auto">
                                        <div class="d-flex flex-column">
                                            <button formaction="course_modules.php?course_id=<?php echo $courseId; ?>" formmethod="POST" name="action" value="reorder" class="btn btn-sm btn-secondary mb-1" <?php echo $i === 0 ? 'disabled' : ''; ?> onclick="this.form.elements['dir'].value='up'"><i class="bi bi-chevron-up"></i></button>
                                            <button formaction="course_modules.php?course_id=<?php echo $courseId; ?>" formmethod="POST" name="action" value="reorder" class="btn btn-sm btn-secondary" <?php echo $i === count($modules) - 1 ? 'disabled' : ''; ?> onclick="this.form.elements['dir'].value='down'"><i class="bi bi-chevron-down"></i></button>
                                            <input type="hidden" name="dir" value="">
                                        </div>
                                    </div>
                                    <div class="col-auto"><span class="badge bg-success">#<?php echo $i + 1; ?></span></div>
                                    <div class="col-md-3"><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($m['title']); ?>" required></div>
                                    <div class="col-md-5"><input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($m['description'] ?? ''); ?>"></div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-check"></i> Opslaan</button>
                                        <button type="submit" formaction="course_modules.php?course_id=<?php echo $courseId; ?>" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Module verwijderen?')"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
