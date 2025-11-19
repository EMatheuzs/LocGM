<?php include __DIR__ . '/partials/header.php';
require_once __DIR__ . '/csrf.php';
?>
<h2>Social • Promoções e Novidades</h2>
<?php if ($user['role'] === 'empresa' && isset($_POST['content'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        echo '<div class="alert">Token de segurança inválido!</div>';
    } else {
        $c = trim($_POST['content']);
        if ($c !== '') {
            add_post($user['email'], $user['company_name'] ?: $user['name'], $c);
            echo '<div class="alert">Post publicado!</div>';
        }
    }
} ?>
<?php if ($user['role'] === 'empresa'): ?>
    <form method="post" class="card vstack gap">
        <label class="label">Nova postagem</label>
        <?php echo csrf_field(); ?>
        <textarea name="content" rows="3" placeholder="Escreva sua promoção..."></textarea>
        <div class="hstack gap"><button class="btn primary">Publicar</button></div>
    </form>
<?php endif; ?>
<div class="vstack gap">
    <?php foreach (array_reverse(posts()) as $p): ?>
        <article class="card soft">
            <div class="card-title"><?php echo htmlspecialchars($p['company_name']); ?></div>
            <p><?php echo nl2br(htmlspecialchars($p['content'])); ?></p>
            <div class="muted small"><?php echo htmlspecialchars($p['created_at']); ?></div>
        </article>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>