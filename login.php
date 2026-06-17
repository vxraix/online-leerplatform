<?php
require_once 'config/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vul alle velden in.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role, first_name, last_name FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

            // Streak bijwerken en welkom-notificatie
            $streak = touchStreakOnLogin((int)$user['id']);
            if ($streak['awarded']) {
                $msg = $streak['streak_days'] > 1
                    ? "Goed bezig! Streak van {$streak['streak_days']} dagen. +" . XP_PER_STREAK_DAY . " XP"
                    : "Welkom! Begin van een nieuwe streak. +" . XP_PER_STREAK_DAY . " XP";
                addNotification((int)$user['id'], 'Dagelijkse login', $msg, 'profile.php', 'bi-fire');
            }

            header('Location: index.php');
            exit;
        } else {
            $error = 'Ongeldige gebruikersnaam of wachtwoord.';
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Inloggen';
include 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="auth-hero animate-in">
                    <h1 class="display-6 text-white">Welkom terug</h1>
                    <p>Log in om verder te gaan met je cursussen.</p>
                </div>
                <div class="card shadow animate-in delay-1">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Inloggen</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Gebruikersnaam</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Wachtwoord</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 py-3 cta-highlight pulse-soft">Inloggen →</button>
                        </form>
                        <hr class="my-4">
                        <p class="text-center mb-0">
                            Nog geen account? <a href="register.php" class="fw-600">Registreer hier</a>
                        </p>
                    </div>
                </div>
                <div class="card mt-3 demo-card animate-in delay-2">
                    <div class="card-body">
                        <h6 class="mb-2"><i class="bi bi-lightning-charge text-warning"></i> Snel proberen?</h6>
                        <small class="text-muted">
                            <strong class="text-white">Admin:</strong> admin / admin123 &nbsp;·&nbsp;
                            <strong class="text-white">Docent:</strong> docent1 / docent123 &nbsp;·&nbsp;
                            <strong class="text-white">Student:</strong> student1 / student123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

