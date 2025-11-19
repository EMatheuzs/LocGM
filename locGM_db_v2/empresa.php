<?php include __DIR__ . '/partials/header.php';
require_once __DIR__ . '/csrf.php';
if ($user['role'] !== 'empresa') {
  echo '<p class="alert">Somente empresas.</p>';
  include __DIR__ . '/partials/footer.php';
  exit;
} ?>
<h2>Painel da Empresa</h2>
<div class="grid two">
  <section class="card">
    <div class="card-title">Meus locais</div>
    <?php
    if (isset($_POST['action']) && $_POST['action'] === 'save_place') {
      // Validar CSRF token
      if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Token de segurança inválido']);
        exit;
      }
      $data = [
        'id' => intval($_POST['id'] ?? 0),
        'name' => trim($_POST['name'] ?? ''),
        'type' => trim($_POST['type'] ?? 'restaurante'),
        'lat' => $_POST['lat'] ?? null,
        'lng' => $_POST['lng'] ?? null,
        'rating' => $_POST['rating'] ?? null,
        'address' => trim($_POST['address'] ?? '')
      ];

      // Validação de servidor
      $errors = [];
      if ($data['name'] === '') {
        $errors['name'] = 'Informe o nome do local.';
      }
      if (!is_numeric($data['lat']) || floatval($data['lat']) < -90 || floatval($data['lat']) > 90) {
        $errors['lat'] = 'Latitude inválida.';
      }
      if (!is_numeric($data['lng']) || floatval($data['lng']) < -180 || floatval($data['lng']) > 180) {
        $errors['lng'] = 'Longitude inválida.';
      }
      if (!is_numeric($data['rating']) || floatval($data['rating']) < 0 || floatval($data['rating']) > 5) {
        $errors['rating'] = 'Nota deve ser entre 0 e 5.';
      }

      $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

      if (!empty($errors)) {
        if ($isAjax) {
          http_response_code(422);
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['status' => 'error', 'errors' => $errors]);
          exit;
        } else {
          echo '<div class="alert">Erro ao salvar local:<ul>';
          foreach ($errors as $field => $m) {
            echo '<li>' . htmlspecialchars($m) . '</li>';
          }
          echo '</ul></div>';
        }
      } else {
        // normalizar tipos numéricos antes de salvar
        $data['lat'] = floatval($data['lat']);
        $data['lng'] = floatval($data['lng']);
        $data['rating'] = floatval($data['rating']);
        $saved = save_place($data, $user['email']);
        if ($isAjax) {
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['status' => $saved ? 'ok' : 'error', 'place' => $saved]);
          exit;
        }
        if ($saved) {
          echo '<div class="alert">Local salvo!</div>';
        } else {
          echo '<div class="alert">Erro ao salvar local.</div>';
        }
      }
    }
    $placesMine = my_places($user['email']);
    ?>
    <div class="vstack gap">
      <?php foreach ($placesMine as $pl): ?>
        <div class="card soft"><b><?php echo htmlspecialchars($pl['name']); ?></b>
          <div class="muted small"><?php echo htmlspecialchars($pl['type']); ?> • Nota
            <?php echo htmlspecialchars($pl['rating']); ?></div>
          <details>
            <summary>Editar</summary>
            <form method="post" class="grid two">
              <input type="hidden" name="action" value="save_place">
              <input type="hidden" name="id" value="<?php echo $pl['id']; ?>">
              <?php echo csrf_field(); ?>
              <label>Nome<input name="name" value="<?php echo $pl['name']; ?>"></label>
              <label>Tipo<select name="type"><?php foreach (['restaurante', 'mercado', 'pousada', 'farmacia', 'turismo'] as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $pl['type'] === $t ? 'selected' : ''; ?>><?php echo $t; ?></option><?php endforeach; ?>
                </select></label>
              <label>Latitude<input name="lat" value="<?php echo $pl['lat']; ?>"></label>
              <label>Longitude<input name="lng" value="<?php echo $pl['lng']; ?>"></label>
              <label>Nota (0-5)<input name="rating" value="<?php echo $pl['rating']; ?>"></label>
              <label>Endereço<input name="address" value="<?php echo htmlspecialchars($pl['address']); ?>"></label>
              <div class="full"><button class="btn">Salvar</button></div>
            </form>
          </details>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="card">
    <div class="card-title">Adicionar novo local</div>
    <form method="post" class="grid two">
      <input type="hidden" name="action" value="save_place">
      <?php echo csrf_field(); ?>
      <label>Nome<input name="name" required></label>
      <label>Tipo<select name="type">
          <option>restaurante</option>
          <option>mercado</option>
          <option>pousada</option>
          <option>farmacia</option>
          <option>turismo</option>
        </select></label>
      <label>Latitude<input name="lat" value="-10.783"></label>
      <label>Longitude<input name="lng" value="-65.338"></label>
      <label>Nota (0-5)<input name="rating" value="4.0"></label>
      <label>Endereço<input name="address"></label>
      <div class="full"><button class="btn primary">Adicionar</button></div>
    </form>
  </section>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>