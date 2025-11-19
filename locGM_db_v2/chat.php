<?php include __DIR__ . '/partials/header.php';
require_once __DIR__ . '/csrf.php';
if (!isset($user)) {
    header('Location: /login.php');
    exit;
}
?>
<h2>Chat</h2>
<?php $partners = $user['role'] === 'visitante' ? companies() : array_merge(demo_visitors(), [['email' => $user['email'], 'name' => '(Você)']]);
$with = $_GET['with'] ?? ($partners[0]['email'] ?? null);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['msg']) && $with) {
  if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    echo '<div class="alert" style="margin: 1rem;">Token de segurança inválido!</div>';
  } else {
    $msg = trim($_POST['msg']);
    if ($msg !== '')
      add_message($user['email'], $with, $msg);
  }
}
$conv = array_values(array_filter(messages(), fn($m) => $with && (($m['from'] === $user['email'] && $m['to'] === $with) || ($m['from'] === $with && $m['to'] === $user['email'])))); ?>
<div class="hsplit">
  <aside class="list"><?php foreach ($partners as $p):
    $label = $p['company_name'] ?? ($p['name'] ?? $p['email']); ?><a
        class="list-item <?php echo ($with === $p['email']) ? 'active' : ''; ?>"
        href="/chat.php?with=<?php echo urlencode($p['email']); ?>"><?php echo htmlspecialchars($label); ?><span
          class="muted small"><?php echo htmlspecialchars($p['email']); ?></span></a><?php endforeach; ?></aside>
  <section class="chat"><?php if ($with): ?>
      <div class="chat-header">Conversando com
        <b><?php $peer = $with;
        foreach ($partners as $p) {
          if ($p['email'] === $with) {
            $peer = $p['company_name'] ?? ($p['name'] ?? $with);
            break;
          }
        }
        echo htmlspecialchars($peer); ?></b>
      </div>
      <div class="chat-body" id="chatBody"><?php foreach ($conv as $m):
        $mine = $m['from'] === $user['email']; ?>
          <div class="bubble <?php echo $mine ? 'mine' : 'theirs'; ?>"><?php echo nl2br(htmlspecialchars($m['content'])); ?>
            <div class="time"><?php echo htmlspecialchars($m['created_at']); ?></div>
          </div><?php endforeach; ?>
      </div>
      <form method="post" class="chat-input">
        <?php echo csrf_field(); ?>
        <input name="msg" placeholder="Mensagem..." autofocus>
        <button class="btn primary">Enviar</button>
      </form><?php else: ?>
      <p class="muted">Nenhum contato selecionado.</p><?php endif; ?>
  </section>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>