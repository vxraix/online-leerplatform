<?php
require_once 'config/config.php';
requireAdmin();

$pdo = getDBConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $role = sanitize($_POST['role'] ?? 'student');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Vul alle velden in.';
    } elseif (!validateEmail($email)) {
        $error = 'Ongeldig e-mailadres.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Wachtwoord moet minimaal ' . PASSWORD_MIN_LENGTH . ' tekens lang zijn.';
    } elseif ($password !== $password_confirm) {
        $error = 'Wachtwoorden komen niet overeen.';
    } elseif (!in_array($role, ['admin', 'docent', 'student'])) {
        $error = 'Ongeldige rol.';
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Gebruikersnaam bestaat al.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'E-mailadres bestaat al.';
            } else {
                // Create user
                $password_hash = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $role])) {
                    $success = 'Gebruiker succesvol toegevoegd.';
                    header('Location: users.php');
                    exit;
                } else {
                    $error = 'Er is een fout opgetreden bij het toevoegen van de gebruiker.';
                }
            }
        }
    }
}

$pageTitle = 'Nieuwe Gebruiker';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-person-plus"></i> Nieuwe Gebruiker</h1>
                <a href="users.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Terug
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Voornaam *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Achternaam *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Gebruikersnaam *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mailadres *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Wachtwoord *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">Minimaal <?php echo PASSWORD_MIN_LENGTH; ?> tekens</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Wachtwoord bevestigen *</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rol *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="student">Student</option>
                                <option value="docent">Docent</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check"></i> Gebruiker Toevoegen
                        </button>
                        <a href="users.php" class="btn btn-secondary">Annuleren</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

