<?php
require_once 'config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Vul alle velden in.';
    } elseif (!validateEmail($email)) {
        $error = 'Ongeldig e-mailadres.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Wachtwoord moet minimaal ' . PASSWORD_MIN_LENGTH . ' tekens lang zijn.';
    } elseif ($password !== $password_confirm) {
        $error = 'Wachtwoorden komen niet overeen.';
    } else {
        $pdo = getDBConnection();
        
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
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, 'student')");
                
                if ($stmt->execute([$username, $email, $password_hash, $first_name, $last_name])) {
                    $success = 'Registratie succesvol! Je kunt nu inloggen.';
                } else {
                    $error = 'Er is een fout opgetreden bij de registratie.';
                }
            }
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Registreren';
include 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="auth-hero animate-in">
                    <h1 class="display-6 text-white">Word lid</h1>
                    <p>Maak een account en start vandaag nog met leren.</p>
                </div>
                <div class="card shadow animate-in delay-1">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-person-plus"></i> Registreren</h4>
                    </div>
                    <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <a href="login.php" class="btn btn-success">Naar inloggen</a>
                    <?php else: ?>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Voornaam</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Achternaam</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Gebruikersnaam</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mailadres</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Wachtwoord</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">Minimaal <?php echo PASSWORD_MIN_LENGTH; ?> tekens</small>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Wachtwoord bevestigen</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 py-3 cta-highlight">Account aanmaken</button>
                        </form>
                    <?php endif; ?>
                    <hr class="my-4">
                    <p class="text-center mb-0">
                        Al een account? <a href="login.php" class="fw-600">Log hier in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>

