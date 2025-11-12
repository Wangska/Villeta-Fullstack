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
	<title>My Notes - Personal Note Manager</title>
	<link rel="stylesheet" href="/styles.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
	<div class="app-wrapper">
		<aside class="sidebar">
			<div class="sidebar-header">
				<div class="logo">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
						<polyline points="14 2 14 8 20 8"></polyline>
						<line x1="16" y1="13" x2="8" y2="13"></line>
						<line x1="16" y1="17" x2="8" y2="17"></line>
						<polyline points="10 9 9 9 8 9"></polyline>
					</svg>
					<h1>My Notes</h1>
				</div>
			</div>
			<div class="sidebar-content">
				<div class="stats">
					<div class="stat-item">
						<span class="stat-number"><?= $total ?></span>
						<span class="stat-label">Total Notes</span>
					</div>
				</div>
			</div>
		</aside>

		<main class="main-content">
			<div class="content-header">
				<h2><?= $editing ? 'Edit Note' : 'Create New Note' ?></h2>
				<?php if ($editing): ?>
					<a href="/" class="btn-icon" title="Cancel editing">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</a>
				<?php endif; ?>
			</div>

			<?php if (!empty($_GET['error'])): ?>
				<div class="alert alert-error">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="12" cy="12" r="10"></circle>
						<line x1="12" y1="8" x2="12" y2="12"></line>
						<line x1="12" y1="16" x2="12.01" y2="16"></line>
					</svg>
					<span><?= h($_GET['error']) ?></span>
				</div>
			<?php endif; ?>
			<?php if (!empty($_GET['success'])): ?>
				<div class="alert alert-success">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<polyline points="20 6 9 17 4 12"></polyline>
					</svg>
					<span><?= h($_GET['success']) ?></span>
				</div>
			<?php endif; ?>

			<div class="form-card">
				<form method="post" class="form">
					<?php if ($editing): ?>
						<input type="hidden" name="action" value="update">
						<input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
					<?php else: ?>
						<input type="hidden" name="action" value="create">
					<?php endif; ?>
					<div class="form-group">
						<label class="form-label">
							<span class="label-text">Note Title</span>
							<input class="form-input" type="text" name="title" maxlength="255" value="<?= h($editing['title'] ?? '') ?>" placeholder="Enter a title for your note...">
						</label>
					</div>
					<div class="form-group">
						<label class="form-label">
							<span class="label-text">Note Content</span>
							<textarea class="form-textarea" name="content" rows="6" placeholder="Write your thoughts here..."><?= h($editing['content'] ?? '') ?></textarea>
						</label>
					</div>
					<div class="form-actions">
						<button class="btn btn-primary" type="submit">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
								<polyline points="17 21 17 13 7 13 7 21"></polyline>
								<polyline points="7 3 7 8 15 8"></polyline>
							</svg>
							<?= $editing ? 'Update Note' : 'Save Note' ?>
						</button>
						<?php if ($editing): ?>
							<a class="btn btn-secondary" href="/">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<line x1="18" y1="6" x2="6" y2="18"></line>
									<line x1="6" y1="6" x2="18" y2="18"></line>
								</svg>
								Cancel
							</a>
						<?php endif; ?>
					</div>
				</form>
			</div>

			<div class="notes-section">
				<div class="section-header">
					<h2>Your Notes</h2>
					<?php if ($notes): ?>
						<span class="notes-count"><?= count($notes) ?> note<?= count($notes) !== 1 ? 's' : '' ?></span>
					<?php endif; ?>
				</div>

				<?php if (!$notes): ?>
					<div class="empty-state">
						<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
							<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
							<polyline points="14 2 14 8 20 8"></polyline>
							<line x1="16" y1="13" x2="8" y2="13"></line>
							<line x1="16" y1="17" x2="8" y2="17"></line>
						</svg>
						<h3>No notes yet</h3>
						<p>Create your first note to get started!</p>
					</div>
				<?php else: ?>
					<div class="notes-grid">
						<?php foreach ($notes as $note): ?>
							<article class="note-card">
								<div class="note-card-header">
									<h3 class="note-card-title"><?= h($note['title'] ?? 'Untitled Note') ?></h3>
									<div class="note-card-menu">
										<a href="/?action=edit&id=<?= (int)$note['id'] ?>" class="note-action-btn" title="Edit note">
											<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
												<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
												<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
											</svg>
										</a>
										<form method="post" onsubmit="return confirm('Are you sure you want to delete this note?');" class="note-action-form">
											<input type="hidden" name="action" value="delete">
											<input type="hidden" name="id" value="<?= (int)$note['id'] ?>">
											<button type="submit" class="note-action-btn note-action-danger" title="Delete note">
												<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
													<polyline points="3 6 5 6 21 6"></polyline>
													<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
												</svg>
											</button>
										</form>
									</div>
								</div>
								<div class="note-card-body">
									<p class="note-card-content"><?= nl2br(h($note['content'] ?? '')) ?></p>
								</div>
								<div class="note-card-footer">
									<div class="note-meta">
										<span class="note-date">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
												<circle cx="12" cy="12" r="10"></circle>
												<polyline points="12 6 12 12 16 14"></polyline>
											</svg>
											<?php
											$updated = new DateTime($note['updated_at']);
											$now = new DateTime();
											$diff = $now->diff($updated);
											if ($diff->days === 0) {
												if ($diff->h === 0) {
													echo $diff->i . ' min ago';
												} else {
													echo $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
												}
											} elseif ($diff->days === 1) {
												echo 'Yesterday';
											} elseif ($diff->days < 7) {
												echo $diff->days . ' days ago';
											} else {
												echo $updated->format('M d, Y');
											}
											?>
										</span>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php
					$maxPage = max(1, (int)ceil($total / $perPage));
					if ($maxPage > 1): ?>
						<nav class="pagination">
							<?php if ($page > 1): ?>
								<a class="pagination-btn" href="/?page=<?= $page - 1 ?>">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<polyline points="15 18 9 12 15 6"></polyline>
									</svg>
									Previous
								</a>
							<?php else: ?>
								<span class="pagination-btn pagination-btn-disabled">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<polyline points="15 18 9 12 15 6"></polyline>
									</svg>
									Previous
								</span>
							<?php endif; ?>
							<div class="pagination-info">
								<span>Page <strong><?= $page ?></strong> of <strong><?= $maxPage ?></strong></span>
							</div>
							<?php if ($page < $maxPage): ?>
								<a class="pagination-btn" href="/?page=<?= $page + 1 ?>">
									Next
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<polyline points="9 18 15 12 9 6"></polyline>
									</svg>
								</a>
							<?php else: ?>
								<span class="pagination-btn pagination-btn-disabled">
									Next
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<polyline points="9 18 15 12 9 6"></polyline>
									</svg>
								</span>
							<?php endif; ?>
						</nav>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</main>
	</div>
</body>
</html>


