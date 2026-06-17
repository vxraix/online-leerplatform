<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();
$userId = (int)$_SESSION['user_id'];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($courseId === 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT c.*, u.first_name AS u_first, u.last_name AS u_last
    FROM courses c
    LEFT JOIN users u ON u.id = c.teacher_id
    WHERE c.id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course) { header('Location: courses.php'); exit; }

// Check voltooid
$progress = getCourseProgress($userId, $courseId);
if (!$progress['is_complete']) {
    header('Location: course_detail.php?id=' . $courseId);
    exit;
}

// Gebruikersnaam
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$me = $stmt->fetch();
$fullName = trim($me['first_name'] . ' ' . $me['last_name']);

// Wanneer afgerond
$stmt = $pdo->prepare("
    SELECT MAX(mc.completed_at) AS done_at
    FROM module_completions mc
    INNER JOIN course_modules cm ON cm.id = mc.module_id
    WHERE mc.user_id = ? AND cm.course_id = ?
");
$stmt->execute([$userId, $courseId]);
$doneAt = $stmt->fetchColumn();

$certId = strtoupper(substr(md5("$userId-$courseId-$doneAt"), 0, 12));
$instructor = trim($course['u_first'] . ' ' . $course['u_last']) ?: $course['instructor'];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Certificaat — <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --blue: #002ef4; --gold: #d4af37; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Outfit', sans-serif;
            background: #0a0a10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .toolbar {
            position: fixed;
            top: 1rem;
            left: 1rem;
            right: 1rem;
            display: flex;
            gap: .5rem;
            justify-content: space-between;
            z-index: 10;
        }
        .toolbar .btn {
            background: rgba(255,255,255,.08);
            color: #fff;
            border: 1px solid rgba(255,255,255,.18);
            padding: .55rem 1rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            font-size: .95rem;
        }
        .toolbar .btn.primary {
            background: linear-gradient(135deg, var(--blue), #7c00ff);
            border-color: transparent;
        }
        .certificate {
            width: min(960px, 100%);
            aspect-ratio: 4 / 3;
            background:
                radial-gradient(ellipse at top right, rgba(0,46,244,.08), transparent 60%),
                radial-gradient(ellipse at bottom left, rgba(212,175,55,.05), transparent 60%),
                #fdfcf7;
            color: #0a0a10;
            padding: 3rem 4rem;
            position: relative;
            box-shadow: 0 40px 100px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.05);
            border-radius: 18px;
            overflow: hidden;
        }
        .certificate::before {
            content: '';
            position: absolute;
            inset: 16px;
            border: 2px solid var(--gold);
            border-radius: 10px;
            pointer-events: none;
        }
        .certificate::after {
            content: '';
            position: absolute;
            inset: 22px;
            border: 1px solid rgba(0,46,244,.2);
            border-radius: 6px;
            pointer-events: none;
        }
        .top { text-align: center; }
        .top .seal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px; height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--blue), #7c00ff);
            color: #fff;
            font-size: 2rem;
            box-shadow: 0 12px 30px rgba(0,46,244,.35);
            margin-bottom: .5rem;
        }
        .top h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            font-weight: 900;
            letter-spacing: .04em;
            color: #0a0a10;
            text-transform: uppercase;
        }
        .top .subtitle { letter-spacing: .4em; font-size: .8rem; color: #888; text-transform: uppercase; margin-top: .3rem; }

        .body-text { text-align: center; margin: 1.8rem 0 1.2rem; color: #555; font-size: 1.05rem; }
        .name {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 3rem;
            text-align: center;
            color: var(--blue);
            letter-spacing: -.02em;
            line-height: 1.1;
        }
        .for-text { text-align: center; color: #555; margin: 1.2rem 0 .5rem; font-size: 1.05rem; }
        .course-title {
            text-align: center;
            font-size: 1.7rem;
            font-weight: 700;
            color: #0a0a10;
            letter-spacing: -.02em;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 2.5rem;
            color: #444;
        }
        .meta .col { text-align: center; flex: 1; }
        .meta .col .label { font-size: .7rem; letter-spacing: .25em; color: #999; text-transform: uppercase; margin-bottom: .25rem; }
        .meta .col .value { font-weight: 600; font-size: 1rem; }
        .meta .col .line { border-top: 1px solid #999; margin: .35rem auto 0; max-width: 70%; }
        .cert-id {
            position: absolute;
            bottom: 24px; right: 36px;
            font-size: .7rem;
            color: #999;
            letter-spacing: .15em;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .certificate { box-shadow: none; border-radius: 0; aspect-ratio: auto; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="btn" href="course_detail.php?id=<?php echo $courseId; ?>"><i class="bi bi-arrow-left"></i> Terug</a>
        <button class="btn primary" onclick="window.print()"><i class="bi bi-printer"></i> Printen / opslaan als PDF</button>
    </div>

    <div class="certificate">
        <div class="top">
            <div class="seal"><i class="bi bi-mortarboard-fill"></i></div>
            <h1>Certificaat</h1>
            <div class="subtitle"><?php echo APP_NAME; ?></div>
        </div>

        <div class="body-text">Hierbij wordt verklaard dat</div>
        <div class="name"><?php echo htmlspecialchars($fullName); ?></div>
        <div class="for-text">met succes de cursus heeft afgerond</div>
        <div class="course-title">"<?php echo htmlspecialchars($course['title']); ?>"</div>

        <div class="meta">
            <div class="col">
                <div class="value"><?php echo htmlspecialchars($instructor); ?></div>
                <div class="line"></div>
                <div class="label">Instructeur</div>
            </div>
            <div class="col">
                <div class="value"><?php echo date('d-m-Y', strtotime($doneAt ?: 'now')); ?></div>
                <div class="line"></div>
                <div class="label">Datum</div>
            </div>
            <div class="col">
                <div class="value"><?php echo (int)$course['duration_hours']; ?> uur</div>
                <div class="line"></div>
                <div class="label">Studiebelasting</div>
            </div>
        </div>

        <div class="cert-id">ID: <?php echo $certId; ?></div>
    </div>
</body>
</html>
