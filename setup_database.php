<?php
/**
 * Database Setup for XAMPP MySQL
 * Creates the leerplatform database and imports database.sql (one file, all tables).
 */

require_once __DIR__ . '/config/database.php';

$force = isset($_GET['force']);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Database Setup</title>";
echo "<style>body{font-family:Outfit,Arial,sans-serif;background:#0a0a10;color:#e8eaef;padding:2rem;line-height:1.6;max-width:720px;margin:0 auto;}";
echo "a{color:#5a8aff;} .ok{color:#7dff9d;} .warn{color:#ffd47a;} .err{color:#ff7a8a;} code{background:#1a1a24;padding:2px 6px;border-radius:4px;}</style></head><body>";
echo "<h1>Database Setup (XAMPP MySQL)</h1>";

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<p class='ok'>✓ Verbonden met MySQL op <code>" . htmlspecialchars(DB_HOST) . "</code></p>";

    $dbname = DB_NAME;
    $stmt = $pdo->query("SHOW DATABASES LIKE " . $pdo->quote($dbname));
    $exists = (bool) $stmt->fetch();

    if ($exists && !$force) {
        echo "<p class='warn'>Database <code>$dbname</code> bestaat al.</p>";
        echo "<p>Om alles opnieuw te installeren (verwijdert bestaande data): ";
        echo "<a href='?force=1'>Opnieuw installeren</a></p>";
        echo "<p>Of importeer handmatig <code>database.sql</code> via ";
        echo "<a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a>.</p>";
        echo "<p><a href='login.php'>→ Naar inloggen</a></p>";
        echo "</body></html>";
        exit;
    }

    if ($exists && $force) {
        echo "<p class='warn'>Bestaande database wordt overschreven…</p>";
    }

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    echo "<p class='ok'>✓ Database <code>$dbname</code> gereed</p>";

    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new RuntimeException('database.sql niet gevonden in projectmap.');
    }

    $sql = file_get_contents($sqlFile);
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $executed = 0;
    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
        $executed++;
    }

    echo "<p class='ok'>✓ $executed SQL-statements uitgevoerd</p>";
    echo "<p class='ok'><strong>Installatie voltooid!</strong> Alle 12 tabellen staan in één database.</p>";
    echo "<h3>Demo-accounts</h3><ul>";
    echo "<li>Admin: <code>admin</code> / <code>admin123</code></li>";
    echo "<li>Leerkracht: <code>leerkracht1</code> / <code>docent123</code></li>";
    echo "<li>Student: <code>student1</code> / <code>student123</code></li>";
    echo "</ul>";
    echo "<p><a href='login.php'>→ Naar inloggen</a></p>";

} catch (PDOException $e) {
    echo "<p class='err'>✗ Databasefout: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Handmatig via XAMPP</h3><ol>";
    echo "<li>Start Apache en MySQL in het XAMPP Control Panel</li>";
    echo "<li>Open <a href='http://localhost/phpmyadmin'>phpMyAdmin</a></li>";
    echo "<li>Maak database <code>leerplatform</code> aan (of gebruik Import)</li>";
    echo "<li>Importeer het bestand <code>database.sql</code></li>";
    echo "</ol>";
} catch (RuntimeException $e) {
    echo "<p class='err'>✗ " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";