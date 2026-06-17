<?php
require_once 'config/config.php';
requireAdmin();

$pdo = getDBConnection();
$error = '';
$success = '';

// Get all users for dropdown
$stmt = $pdo->query("SELECT id, username, first_name, last_name FROM users ORDER BY username");
$users = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $subscription_type = sanitize($_POST['subscription_type'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if ($user_id === 0) {
        $error = 'Selecteer een gebruiker.';
    } elseif (empty($subscription_type) || empty($start_date)) {
        $error = 'Type en startdatum zijn verplicht.';
    } elseif (!empty($end_date) && strtotime($end_date) < strtotime($start_date)) {
        $error = 'Einddatum moet na startdatum zijn.';
    } else {
        // Check if user already has active subscription
        if ($is_active) {
            $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())");
            $stmt->execute([$user_id]);
            if ($stmt->fetch()) {
                $error = 'Deze gebruiker heeft al een actief abonnement.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, subscription_type, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
                $end_date_value = !empty($end_date) ? $end_date : null;
                
                if ($stmt->execute([$user_id, $subscription_type, $start_date, $end_date_value, $is_active])) {
                    $success = 'Abonnement succesvol toegevoegd.';
                    header('Location: subscriptions.php');
                    exit;
                } else {
                    $error = 'Er is een fout opgetreden bij het toevoegen van het abonnement.';
                }
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, subscription_type, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
            $end_date_value = !empty($end_date) ? $end_date : null;
            
            if ($stmt->execute([$user_id, $subscription_type, $start_date, $end_date_value, $is_active])) {
                $success = 'Abonnement succesvol toegevoegd.';
                header('Location: subscriptions.php');
                exit;
            } else {
                $error = 'Er is een fout opgetreden bij het toevoegen van het abonnement.';
            }
        }
    }
}

$pageTitle = 'Nieuw Abonnement';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-plus-circle"></i> Nieuw Abonnement</h1>
                <a href="subscriptions.php" class="btn btn-secondary">
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
                            <label for="subscription_type" class="form-label">Type *</label>
                            <input type="text" class="form-control" id="subscription_type" name="subscription_type" placeholder="bijv. Premium, Basic, Pro" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Datum *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">Eind Datum</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                                <small class="text-muted">Laat leeg voor onbeperkt abonnement</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Actief
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check"></i> Abonnement Toevoegen
                        </button>
                        <a href="subscriptions.php" class="btn btn-secondary">Annuleren</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

