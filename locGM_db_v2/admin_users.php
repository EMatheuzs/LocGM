<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/csrf.php';
@include_once __DIR__ . '/db.php';
require_login();

$users = function_exists('get_users_from_db') ? get_users_from_db() : [];
?>
<h2>Usuários cadastrados (DB)</h2>
<p class="muted small">Lista de usuários armazenados no banco de dados.</p>

<?php if(empty($users)): ?>
  <div class="card">Nenhum usuário encontrado no banco.</div>
<?php else: ?>
  <div class="card">
    <table class="table">
      <thead><tr><th>ID</th><th>E-mail</th><th>Nome</th><th>Role</th><th>Company</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
        <tr data-id="<?php echo htmlspecialchars($u['id']); ?>">
          <td class="col-id"><?php echo htmlspecialchars($u['id']); ?></td>
          <td class="col-email"><?php echo htmlspecialchars($u['email']); ?></td>
          <td class="col-name"><?php echo htmlspecialchars($u['name']); ?></td>
          <td class="col-role"><?php echo htmlspecialchars($u['role']); ?></td>
          <td class="col-company"><?php echo htmlspecialchars($u['company_name']); ?></td>
          <td>
            <button class="btn tiny edit-user">Editar</button>
            <button class="btn tiny danger delete-user">Excluir</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <meta name="csrf-token" content="<?php echo htmlspecialchars(get_csrf_token()); ?>">
  <script src="/static/admin.js"></script>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
