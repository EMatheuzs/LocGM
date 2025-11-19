<?php include __DIR__ . '/partials/header.php'; ?>
<h2>Mapa de Guajará-Mirim</h2>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css"
  crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js" crossorigin=""></script>

<div class="map-wrap">
  <div id="map" class="map"></div>
  <div id="routeInfo" class="route-info muted small">Clique em um local para traçar a rota a partir da sua localização.
  </div>
</div>

<script>
  const map = L.map('map', { zoomControl: true }).setView([-10.783, -65.338], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);

  const places = <?php echo json_encode(places()); ?>;
  const focus = <?php echo isset($_GET['focus']) ? intval($_GET['focus']) : 'null'; ?>;
  const isCompany = <?php echo json_encode($user['role'] === 'empresa'); ?>;
  let focusMarker = null;

  let currentPos = null;
  let currentMarker = null;
  let routingControl = null;
  const routeInfo = document.getElementById('routeInfo');

  // Obter localização do usuário
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function (pos) {
      currentPos = [pos.coords.latitude, pos.coords.longitude];
      currentMarker = L.circleMarker(currentPos, { radius: 7 }).addTo(map).bindPopup('Você está aqui');
    }, function (err) {
      routeInfo.textContent = 'Não foi possível obter sua localização automática. Você ainda pode visualizar os locais.';
    }, { enableHighAccuracy: true, timeout: 6000, maximumAge: 0 });
  } else {
    routeInfo.textContent = 'Seu navegador não suporta geolocalização.';
  }

  // Marcadores dos locais existentes
  places.forEach(p => {
    const m = L.marker([p.lat, p.lng]).addTo(map);
    m.bindPopup(`<b>${p.name}</b><br>${p.type.toUpperCase()}<br>Nota: ${p.rating}<br><small>${p.address ?? ''}</small><br><button class="btn tiny route-btn" data-lat="${p.lat}" data-lng="${p.lng}">Traçar rota</button>`);
    m.on('click', () => {
      if (focus && p.id === focus) { focusMarker = m; }
      if (currentPos) { drawRoute(currentPos, [p.lat, p.lng]); }
    });
    if (focus && p.id === focus) { focusMarker = m; }
  });

  if (focusMarker) { map.setView(focusMarker.getLatLng(), 17); focusMarker.openPopup(); }

  map.on('popupopen', function (e) {
    const btn = e.popup.getElement().querySelector('.route-btn');
    if (btn) {
      btn.addEventListener('click', () => {
        const lat = parseFloat(btn.getAttribute('data-lat'));
        const lng = parseFloat(btn.getAttribute('data-lng'));
        if (!currentPos) {
          routeInfo.textContent = 'Ative a localização do navegador para traçar a rota.';
          return;
        }
        drawRoute(currentPos, [lat, lng]);
      }, { once: true });
    }
  });

  // Permitir adicionar um local clicando em qualquer parte do mapa (apenas para empresas)
  let newPlaceMarker = null;
  map.on('click', function (e) {
    if (!isCompany) {
      routeInfo.textContent = 'Somente contas com papel "empresa" podem adicionar locais. Acesse o painel Empresa para adicionar.';
      return;
    }
    if (newPlaceMarker) { map.removeLayer(newPlaceMarker); }
    const lat = e.latlng.lat.toFixed(6);
    const lng = e.latlng.lng.toFixed(6);
    newPlaceMarker = L.marker([lat, lng], { draggable: true }).addTo(map);
    const popupHtml = `
    <form action="/empresa.php" method="post" class="vstack gap" style="min-width:240px;">
      <input type="hidden" name="action" value="save_place">
      <label>Nome<input name="name" required><div class="field-error name-error" style="color:#b00020;font-size:12px;margin-top:4px"></div></label>
      <label>Tipo<select name="type"><option>restaurante</option><option>mercado</option><option>pousada</option><option>farmacia</option><option>turismo</option></select></label>
      <label>Latitude<input name="lat" value="${lat}"><div class="field-error lat-error" style="color:#b00020;font-size:12px;margin-top:4px"></div></label>
      <label>Longitude<input name="lng" value="${lng}"><div class="field-error lng-error" style="color:#b00020;font-size:12px;margin-top:4px"></div></label>
      <label>Nota (0-5)<input name="rating" value="4.0"><div class="field-error rating-error" style="color:#b00020;font-size:12px;margin-top:4px"></div></label>
      <label>Endereço<input name="address"></label>
      <div class="hstack"><button type="submit" id="savePlaceBtn" class="btn">Salvar no painel</button><button type="button" class="btn" id="cancelAdd">Cancelar</button></div>
    </form>
  `;
    newPlaceMarker.bindPopup(popupHtml).openPopup();

    newPlaceMarker.on('dragend', function (ev) {
      const p = ev.target.getLatLng();
      const popup = ev.target.getPopup();
      if (!popup) return;
      const el = popup.getElement();
      if (!el) return;
      const latInput = el.querySelector('input[name="lat"]');
      const lngInput = el.querySelector('input[name="lng"]');
      if (latInput) latInput.value = p.lat.toFixed(6);
      if (lngInput) lngInput.value = p.lng.toFixed(6);
    });

    newPlaceMarker.on('popupopen', function (ev) {
      const form = ev.popup.getElement().querySelector('form[action="/empresa.php"]');
      const cancelBtn = ev.popup.getElement().querySelector('#cancelAdd');
      if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
          map.removeLayer(newPlaceMarker);
          newPlaceMarker = null;
          routeInfo.textContent = 'Criação cancelada.';
        });
      }
      if (form) attachPlaceFormListener(form, newPlaceMarker);
    });
  });

  function attachPlaceFormListener(form, marker) {
    if (!form || form.dataset.attached) return;
    form.dataset.attached = '1';

    form.addEventListener('submit', async function (ev) {
      ev.preventDefault();

      const clearFieldErrors = (f) => { f.querySelectorAll('.field-error').forEach(el => el.textContent = ''); };
      clearFieldErrors(form);

      const name = (form.querySelector('input[name="name"]').value || '').trim();
      const latV = form.querySelector('input[name="lat"]').value;
      const lngV = form.querySelector('input[name="lng"]').value;
      const ratingV = form.querySelector('input[name="rating"]').value;

      const clientErrors = {};
      if (!name) clientErrors['name'] = 'Informe o nome do local.';
      const latNum = parseFloat(latV);
      if (isNaN(latNum) || latNum < -90 || latNum > 90) clientErrors['lat'] = 'Latitude inválida.';
      const lngNum = parseFloat(lngV);
      if (isNaN(lngNum) || lngNum < -180 || lngNum > 180) clientErrors['lng'] = 'Longitude inválida.';
      const ratingNum = parseFloat(ratingV);
      if (isNaN(ratingNum) || ratingNum < 0 || ratingNum > 5) clientErrors['rating'] = 'Nota deve ser entre 0 e 5.';

      if (Object.keys(clientErrors).length) {
        for (const k in clientErrors) {
          const el = form.querySelector('.' + k + '-error');
          if (el) el.textContent = clientErrors[k];
        }
        routeInfo.textContent = 'Corrija os campos inválidos.';
        return;
      }

      const submitBtn = form.querySelector('#savePlaceBtn');
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Salvando...'; }
      routeInfo.textContent = 'Salvando local...';

      const fd = new FormData(form);
      try {
        const res = await fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await res.json();
        if (json && json.status === 'ok' && json.place) {
          const pl = json.place;
          pl.lat = parseFloat(pl.lat);
          pl.lng = parseFloat(pl.lng);
          pl.rating = parseFloat(pl.rating);
          const m = L.marker([pl.lat, pl.lng]).addTo(map);
          m.bindPopup(`<b>${pl.name}</b><br>${(pl.type || '').toUpperCase()}<br>Nota: ${pl.rating}<br><small>${pl.address ?? ''}</small><br><button class="btn tiny route-btn" data-lat="${pl.lat}" data-lng="${pl.lng}">Traçar rota</button>`);
          m.on('click', () => { if (currentPos) drawRoute(currentPos, [pl.lat, pl.lng]); });
          places.push(pl);
          map.closePopup();
          if (marker) { map.removeLayer(marker); }
          routeInfo.textContent = 'Local salvo e adicionado ao mapa.';
        } else if (json && json.status === 'error' && json.errors) {
          for (const k in json.errors) {
            const el = form.querySelector('.' + k + '-error');
            if (el) el.textContent = json.errors[k];
          }
          routeInfo.textContent = 'Corrija os erros e tente novamente.';
        } else {
          routeInfo.textContent = 'Erro ao salvar local.';
        }
      } catch (err) {
        routeInfo.textContent = 'Erro ao salvar local (conexão).';
      } finally {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Salvar no painel'; }
      }
    });
  }

  function drawRoute(from, to) {
    if (routingControl) { map.removeControl(routingControl); }
    routingControl = L.Routing.control({
      waypoints: [L.latLng(from[0], from[1]), L.latLng(to[0], to[1])],
      router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
      lineOptions: { addWaypoints: false },
      show: false,
      collapsible: true
    }).addTo(map);

    routingControl.on('routesfound', function (e) {
      const route = e.routes[0];
      const km = (route.summary.totalDistance / 1000).toFixed(2);
      const min = Math.round(route.summary.totalTime / 60);
      routeInfo.textContent = `Distância: ${km} km • Tempo estimado: ${min} min`;
    });
    routingControl.on('routingerror', function () {
      routeInfo.textContent = 'Não foi possível calcular a rota agora.';
    });
  }
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>