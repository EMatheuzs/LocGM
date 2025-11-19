<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/csrf.php';
// Tenta usar DB; se não, informa ao usuário
@include_once __DIR__ . '/db.php';
require_login();

$companies = function_exists('get_companies_from_db') ? get_companies_from_db() : [];
?>
<h2>Empresas cadastradas (DB)</h2>
<p class="muted small">Lista de empresas armazenadas no banco de dados.</p>

<?php if(empty($companies)): ?>
  <div class="card">Nenhuma empresa encontrada no banco.</div>
<?php else: ?>
  <div class="card">
    <table class="table">
      <thead><tr><th>ID</th><th>E-mail</th><th>Nome</th><th>Endereço</th><th>Telefone</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach($companies as $c): ?>
        <tr data-id="<?php echo htmlspecialchars($c['id']); ?>">
          <td class="col-id"><?php echo htmlspecialchars($c['id']); ?></td>
          <td class="col-email"><?php echo htmlspecialchars($c['email']); ?></td>
          <td class="col-name"><?php echo htmlspecialchars($c['company_name']); ?></td>
          <td class="col-address"><?php echo htmlspecialchars($c['address']); ?></td>
          <td class="col-phone"><?php echo htmlspecialchars($c['phone']); ?></td>
          <td>
            <button class="btn tiny edit-company">Editar</button>
            <button class="btn tiny danger delete-company">Excluir</button>
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
