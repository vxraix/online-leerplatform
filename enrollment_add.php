<?php
require_once 'config/config.php';
requireAdmin();

$pdo = getDBConnection();
$error = '';
$success = '';

// Get all users and courses for dropdowns
$stmt = $pdo->query("SELECT id, username, first_name, last_name FROM users ORDER BY username");
$users = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, title FROM courses WHERE is_active = 1 ORDER BY title");
$courses = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    
    // Validation
    if ($user_id === 0 || $course_id === 0) {
        $error = 'Selecteer een gebruiker en cursus.';
    } else {
        // Check if enrollment already exists
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        if ($stmt->fetch()) {
            $error = 'Deze gebruiker is al ingeschreven voor deze cursus.';
        } else {
            // Check if user has active subscription
            $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())");
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) {
                $error = 'Deze gebruiker heeft geen actief abonnement.';
            } else {
                // Create enrollment
                $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
                
                if ($stmt->execute([$user_id, $course_id])) {
                    $success = 'Inschrijving succesvol toegevoegd.';
                    header('Location: enrollments.php');
                    exit;
                } else {
                    $error = 'Er is een fout opgetreden bij het toevoegen van de inschrijving.';
                }
            }
        }
    }
}

$pageTitle = 'Nieuwe Inschrijving';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-plus-circle"></i> Nieuwe Inschrijving</h1>
                <a href="enrollments.php" class="btn btn-secondary">
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
                            <label for="user_id" class="form-label">Gebruiker *</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Selecteer gebruiker...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['username'] . ' - ' . $user['first_name'] . ' ' . $user['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Cursus *</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Selecteer cursus...</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check"></i> Inschrijving Toevoegen
                        </button>
                        <a href="enrollments.php" class="btn btn-secondary">Annuleren</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

