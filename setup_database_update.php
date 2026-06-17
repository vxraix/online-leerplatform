<?php
/**
 * @deprecated Use setup_database.php or import database.sql directly.
 * Docent role and teacher_id are now included in database.sql.
 */
header('Location: setup_database.php' . (isset($_GET['force']) ? '?force=1' : ''), true, 302);
exit;