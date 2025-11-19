<?php include __DIR__ . '/partials/header.php';
require_once __DIR__ . '/csrf.php';
?>
<h2>Meu perfil</h2>
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    echo '<div class="alert">Token de segurança inválido!</div>';
  } else {
    $user['name'] = trim($_POST['name'] ?? $user['name']);
    $user['phone'] = trim($_POST['phone'] ?? '');
    $user['address'] = trim($_POST['address'] ?? '');
    if ($user['role'] === 'empresa') {
      $user['company_name'] = trim($_POST['company_name'] ?? $user['company_name']);
      $user['prices_note'] = trim($_POST['prices_note'] ?? '');
    }
    set_user($user);
    echo '<div class="alert">Perfil atualizado!</div>';
  }
} ?>
<form method="post" class="grid two">
  <?php echo csrf_field(); ?>
  <label class="vstack">Nome<input name="name" value="<?php echo htmlspecialchars($user['name']); ?>"></label>
  <label class="vstack">E-mail<input value="<?php echo htmlspecialchars($user['email']); ?>" disabled></label>
  <label class="vstack">Telefone<input name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>"></label>
  <label class="vstack">Endereço<input name="address" value="<?php echo htmlspecialchars($user['address']); ?>"></label>
  <?php if ($user['role'] === 'empresa'): ?>
    <label class="vstack">Nome da Empresa<input name="company_name" value="<?php echo htmlspecialchars($user['company_name']); ?>"></label>
    <label class="vstack">Preços / Observações<textarea name="prices_note" rows="3"><?php echo htmlspecialchars($user['prices_note']); ?></textarea></label>
  <?php endif; ?>
  <div class="full"><button class="btn primary">Salvar</button></div>
</form>
<?php include __DIR__ . '/partials/footer.php'; ?>