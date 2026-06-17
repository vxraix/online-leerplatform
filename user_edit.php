<?php
require_once 'config/config.php';
requireAdmin();

$pdo = getDBConnection();
$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header('Location: users.php');
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $role = sanitize($_POST['role'] ?? 'student');
    
    // Validation
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
        $error = 'Vul alle verplichte velden in.';
    } elseif (!validateEmail($email)) {
        $error = 'Ongeldig e-mailadres.';
    } elseif (!empty($password) && strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Wachtwoord moet minimaal ' . PASSWORD_MIN_LENGTH . ' tekens lang zijn.';
    } elseif (!in_array($role, ['admin', 'docent', 'student'])) {
        $error = 'Ongeldige rol.';
    } else {
        // Check if username exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) {
            $error = 'Gebruikersnaam bestaat al.';
        } else {
            // Check if email exists (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $error = 'E-mailadres bestaat al.';
            } else {
                // Update user
                if (!empty($password)) {
                    $password_hash = hashPassword($password);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ?, first_name = ?, last_name = ?, role = ? WHERE id = ?");
                    $result = $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $role, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ? WHERE id = ?");
                    $result = $stmt->execute([$username, $email, $first_name, $last_name, $role, $id]);
                }
                
                if ($result) {
                    $success = 'Gebruiker succesvol bijgewerkt.';
                    header('Location: users.php');
                    exit;
                } else {
                    $error = 'Er is een fout opgetreden bij het bijwerken van de gebruiker.';
                }
            }
        }
    }
    
    // Update user data for form
    $user = [
        'username' => $username,
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'role' => $role
    ];
}

$pageTitle = 'Gebruiker Bewerken';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-pencil"></i> Gebruiker Bewerken</h1>
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
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Achternaam *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Gebruikersnaam *</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mailadres *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Nieuw Wachtwoord</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-muted">Laat leeg om huidige wachtwoord te behouden. Minimaal <?php echo PASSWORD_MIN_LENGTH; ?> tekens.</small>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rol *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="docent" <?php echo $user['role'] === 'docent' ? 'selected' : ''; ?>>Docent</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check"></i> Opslaan
                        </button>
                        <a href="users.php" class="btn btn-secondary">Annuleren</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

