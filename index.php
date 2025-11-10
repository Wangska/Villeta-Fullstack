<?php
declare(strict_types=1);

// Simple Notes App (no users) - Single-file controller/view

// Load environment and DB
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'default';
$dbUser = getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
try {
	$pdo = new PDO($dsn, $dbUser, $dbPass, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
} catch (Throwable $e) {
	http_response_code(500);
	echo "<h1>Database connection failed</h1>";
	echo "<pre>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
	exit;
}

function h(?string $v): string {
	return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Handle actions
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($method === 'POST') {
	if ($action === 'create') {
		$title = trim($_POST['title'] ?? '');
		$content = trim($_POST['content'] ?? '');
		if ($title === '' && $content === '') {
			header('Location: /?error=Empty%20note');
			exit;
		}
		$stmt = $pdo->prepare("INSERT INTO notes (title, content) VALUES (:title, :content)");
		$stmt->execute([
			':title' => $title === '' ? null : $title,
			':content' => $content === '' ? null : $content,
		]);
		header('Location: /?success=Note%20created');
		exit;
	}
	if ($action === 'update') {
		$id = (int)($_POST['id'] ?? 0);
		$title = trim($_POST['title'] ?? '');
		$content = trim($_POST['content'] ?? '');
		$stmt = $pdo->prepare("UPDATE notes SET title = :title, content = :content, updated_at = NOW() WHERE id = :id");
		$stmt->execute([
			':title' => $title === '' ? null : $title,
			':content' => $content === '' ? null : $content,
			':id' => $id,
		]);
		header('Location: /?success=Note%20updated');
		exit;
	}
	if ($action === 'delete') {
		$id = (int)($_POST['id'] ?? 0);
		$stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id");
		$stmt->execute([':id' => $id]);
		header('Location: /?success=Note%20deleted');
		exit;
	}
}

// Fetch notes (simple pagination)
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = (int)$pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$stmt = $pdo->prepare("SELECT id, title, content, created_at, updated_at FROM notes ORDER BY updated_at DESC, created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$notes = $stmt->fetchAll();

$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
	$eid = (int)$_GET['id'];
	$estmt = $pdo->prepare("SELECT id, title, content FROM notes WHERE id = :id");
	$estmt->execute([':id' => $eid]);
	$editing = $estmt->fetch() ?: null;
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Notes</title>
	<link rel="stylesheet" href="/styles.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
	<header class="header">
		<h1>Notes</h1>
	</header>

	<?php if (!empty($_GET['error'])): ?>
		<div class="alert alert-error"><?= h($_GET['error']) ?></div>
	<?php endif; ?>
	<?php if (!empty($_GET['success'])): ?>
		<div class="alert alert-success"><?= h($_GET['success']) ?></div>
	<?php endif; ?>

	<section class="card">
		<h2><?= $editing ? 'Edit note' : 'Add a new note' ?></h2>
		<form method="post" class="form">
			<?php if ($editing): ?>
				<input type="hidden" name="action" value="update">
				<input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
			<?php else: ?>
				<input type="hidden" name="action" value="create">
			<?php endif; ?>
			<label class="field">
				<span class="label">Title</span>
				<input class="input" type="text" name="title" maxlength="255" value="<?= h($editing['title'] ?? '') ?>" placeholder="Optional title">
			</label>
			<label class="field">
				<span class="label">Content</span>
				<textarea class="textarea" name="content" rows="4" placeholder="Write your note..."><?= h($editing['content'] ?? '') ?></textarea>
			</label>
			<div class="actions">
				<button class="btn btn-primary" type="submit"><?= $editing ? 'Update' : 'Add note' ?></button>
				<?php if ($editing): ?>
					<a class="btn" href="/">Cancel</a>
				<?php endif; ?>
			</div>
		</form>
	</section>

	<section class="card">
		<h2>All notes</h2>
		<?php if (!$notes): ?>
			<p class="muted">No notes yet.</p>
		<?php else: ?>
			<ul class="notes">
				<?php foreach ($notes as $note): ?>
					<li class="note">
						<div class="note-head">
							<strong class="note-title"><?= h($note['title'] ?? '(no title)') ?></strong>
							<div class="note-meta">
								<span>Updated: <?= h($note['updated_at']) ?></span>
								<span>Created: <?= h($note['created_at']) ?></span>
							</div>
						</div>
						<div class="note-body">
							<pre><?= h($note['content'] ?? '') ?></pre>
						</div>
						<div class="note-actions">
							<a class="btn" href="/?action=edit&id=<?= (int)$note['id'] ?>">Edit</a>
							<form method="post" onsubmit="return confirm('Delete this note?');">
								<input type="hidden" name="action" value="delete">
								<input type="hidden" name="id" value="<?= (int)$note['id'] ?>">
								<button class="btn btn-danger" type="submit">Delete</button>
							</form>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			$maxPage = max(1, (int)ceil($total / $perPage));
			if ($maxPage > 1): ?>
				<nav class="pagination">
					<?php if ($page > 1): ?>
						<a class="btn" href="/?page=<?= $page - 1 ?>">&larr; Prev</a>
					<?php endif; ?>
					<span class="muted">Page <?= $page ?> of <?= $maxPage ?></span>
					<?php if ($page < $maxPage): ?>
						<a class="btn" href="/?page=<?= $page + 1 ?>">Next &rarr;</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>
	</section>

	<footer class="footer">
		<p class="muted">Simple PHP Notes App</p>
	</footer>
</div>
</body>
</html>


