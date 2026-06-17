<?php
require_once 'config/config.php';
requireAdminOrTeacher();

$pdo = getDBConnection();
$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header('Location: courses.php');
    exit;
}

// Get course data
$stmt = $pdo->prepare("SELECT id, title, description, instructor, teacher_id, duration_hours, price, is_active FROM courses WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: courses.php');
    exit;
}

// Docent mag alleen eigen cursus bewerken
if (isTeacher() && (int)$course['teacher_id'] !== (int)$_SESSION['user_id']) {
    header('Location: courses.php');
    exit;
}

// Lijst docenten (alleen voor admin)
$teachers = [];
if (isAdmin()) {
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'docent' ORDER BY first_name, last_name");
    $teachers = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $duration_hours = isset($_POST['duration_hours']) ? (int)$_POST['duration_hours'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.00;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (isTeacher()) {
        // Docent mag teacher_id niet veranderen, en instructor blijft eigen naam
        $teacher_id = (int)$_SESSION['user_id'];
        $instructor = trim(($_SESSION['user_name'] ?? '')) ?: sanitize($_POST['instructor'] ?? $course['instructor']);
    } else {
        $instructor = sanitize($_POST['instructor'] ?? '');
        $teacher_id = isset($_POST['teacher_id']) && $_POST['teacher_id'] !== '' ? (int)$_POST['teacher_id'] : null;
    }

    if (empty($title) || empty($instructor)) {
        $error = 'Titel en instructeur zijn verplicht.';
    } elseif ($duration_hours < 0) {
        $error = 'Duur moet een positief getal zijn.';
    } elseif ($price < 0) {
        $error = 'Prijs moet een positief getal zijn.';
    } else {
        $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, instructor = ?, teacher_id = ?, duration_hours = ?, price = ?, is_active = ? WHERE id = ?");

        if ($stmt->execute([$title, $description, $instructor, $teacher_id, $duration_hours, $price, $is_active, $id])) {
            header('Location: courses.php');
            exit;
        } else {
            $error = 'Er is een fout opgetreden bij het bijwerken van de cursus.';
        }
    }
    
    $course = [
        'id' => $id,
        'title' => $title,
        'description' => $description,
        'instructor' => $instructor,
        'teacher_id' => $teacher_id,
        'duration_hours' => $duration_hours,
        'price' => $price,
        'is_active' => $is_active
    ];
}

$pageTitle = 'Cursus Bewerken';
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
                <h1 class="h2"><i class="bi bi-pencil"></i> Cursus Bewerken</h1>
                <a href="courses.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Terug
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titel *</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Beschrijving</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($course['description']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="instructor" class="form-label">Instructeur *</label>
                                <?php if (isTeacher()): ?>
                                    <input type="text" class="form-control" id="instructor" name="instructor" value="<?php echo htmlspecialchars($course['instructor']); ?>" readonly>
                                <?php else: ?>
                                    <input type="text" class="form-control" id="instructor" name="instructor" value="<?php echo htmlspecialchars($course['instructor']); ?>" required>
                                <?php endif; ?>
                            </div>
                            <?php if (isAdmin()): ?>
                            <div class="col-md-6 mb-3">
                                <label for="teacher_id" class="form-label">Docent (account)</label>
                                <select class="form-select" id="teacher_id" name="teacher_id">
                                    <option value="">— Geen account gekoppeld —</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?php echo $t['id']; ?>" <?php echo (int)$course['teacher_id'] === (int)$t['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6 mb-3">
                                <label for="duration_hours" class="form-label">Duur (uren)</label>
                                <input type="number" class="form-control" id="duration_hours" name="duration_hours" min="0" value="<?php echo $course['duration_hours']; ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Prijs (€)</label>
                                <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" value="<?php echo $course['price']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo $course['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Actief
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check"></i> Opslaan
                        </button>
                        <a href="courses.php" class="btn btn-secondary">Annuleren</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
