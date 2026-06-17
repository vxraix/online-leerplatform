<?php
require_once 'config/config.php';
requireAdmin();

$pdo = getDBConnection();
$error = '';
$success = '';

// Handle delete/end subscription
if (isset($_GET['end']) && is_numeric($_GET['end'])) {
    $id = (int)$_GET['end'];
    $stmt = $pdo->prepare("UPDATE subscriptions SET is_active = 0, end_date = CURDATE() WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'Abonnement succesvol beëindigd.';
    } else {
        $error = 'Fout bij beëindigen van abonnement.';
    }
}

// Search and filter
$search = sanitize($_GET['search'] ?? '');
$active_filter = sanitize($_GET['active'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR s.subscription_type LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($active_filter !== '') {
    $where[] = "s.is_active = ?";
    $params[] = $active_filter === '1' ? 1 : 0;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get subscriptions with user info
$sql = "SELECT s.*, u.username, u.email, u.first_name, u.last_name 
        FROM subscriptions s 
        INNER JOIN users u ON s.user_id = u.id 
        $where_sql 
        ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subscriptions = $stmt->fetchAll();

$pageTitle = 'Abonnementen';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-credit-card"></i> Abonnementen</h1>
                <a href="subscription_add.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nieuw Abonnement
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Zoek op gebruiker of type..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="active">
                                <option value="">Alle status</option>
                                <option value="1" <?php echo $active_filter === '1' ? 'selected' : ''; ?>>Actief</option>
                                <option value="0" <?php echo $active_filter === '0' ? 'selected' : ''; ?>>Beëindigd</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-search"></i> Zoeken
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="subscriptions.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Subscriptions Table -->
            <div class="card">
                <div class="card-header">
                    <h5>Abonnementen (<?php echo count($subscriptions); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Gebruiker</th>
                                    <th>Type</th>
                                    <th>Start Datum</th>
                                    <th>Eind Datum</th>
                                    <th>Status</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subscriptions)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Geen abonnementen gevonden.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subscriptions as $sub): ?>
                                        <tr>
                                            <td><?php echo $sub['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($sub['username']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($sub['subscription_type']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($sub['start_date'])); ?></td>
                                            <td><?php echo $sub['end_date'] ? date('d-m-Y', strtotime($sub['end_date'])) : '-'; ?></td>
                                            <td>
                                                <?php if ($sub['is_active']): ?>
                                                    <span class="badge bg-success">Actief</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Beëindigd</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="subscription_edit.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($sub['is_active']): ?>
                                                    <a href="subscriptions.php?end=<?php echo $sub['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Weet je zeker dat je dit abonnement wilt beëindigen?')">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

