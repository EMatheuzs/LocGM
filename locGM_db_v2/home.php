<?php include __DIR__ . '/partials/header.php'; ?>
<h2>Locais em destaque</h2>
<div class="tabs hstack gap wrap">
  <a class="pill" href="?t=restaurante">Restaurantes</a>
  <a class="pill" href="?t=mercado">Mercados</a>
  <a class="pill" href="?t=pousada">Pousadas</a>
  <a class="pill" href="?t=farmacia">Farmácias</a>
  <a class="pill" href="?t=turismo">Turismo</a>
</div>
<?php $type = $_GET['t'] ?? 'restaurante';
$rows = array_values(array_filter(places(), fn($p) => $p['type'] === $type));
usort($rows, fn($a, $b) => $b['rating'] <=> $a['rating']); ?>
<div class="grid cards">
  <?php foreach ($rows as $r): ?>
    <div class="card soft">
      <div class="card-title"><?php echo htmlspecialchars($r['name']); ?></div>
      <div class="muted small"><?php echo strtoupper(htmlspecialchars($r['type'])); ?> •
        <?php echo htmlspecialchars($r['address']); ?></div>
      <div class="stars"><?php $full = floor($r['rating']);
      for ($i = 0; $i < 5; $i++) {
        echo $i < $full ? '★' : '☆';
      } ?>
        <span class="muted"><?php echo number_format($r['rating'], 1, ',', '.'); ?></span>
      </div>
      <div class="hstack gap">
        <a class="btn ghost" href="/map.php?focus=<?php echo $r['id']; ?>">Ver no mapa</a>
        <?php if ($r['company_email']): ?>
          <a class="btn primary" href="/chat.php?with=<?php echo urlencode($r['company_email']); ?>">Conversar</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>