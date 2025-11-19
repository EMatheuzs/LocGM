/**
 * admin.js - Lógica de edição/deleção de empresas e usuários via AJAX
 * Utilizado por: admin_companies.php, admin_users.php
 */

/**
 * Carrega o token CSRF do DOM ou da sessão
 * @returns {string} Token CSRF
 */
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta) return meta.getAttribute('content');
  // Fallback: procurar em input hidden na página
  const input = document.querySelector('input[name="csrf_token"]');
  return input ? input.value : '';
}

/**
 * Converte uma célula de texto em um input editável
 * @param {HTMLElement} cell - Célula da tabela
 * @param {string} inputName - Nome do input
 * @param {string} value - Valor inicial
 * @param {string} type - Tipo de input (text, select, etc.)
 */
function convertCellToInput(cell, inputName, value, type = 'text') {
  if (type === 'select') {
    const selectOptions = {
      role: '<select name="role"><option value="visitante">visitante</option><option value="empresa">empresa</option></select>'
    };
    cell.innerHTML = selectOptions[inputName] || '<input type="text" name="' + inputName + '" value="' + escapeHtml(value) + '">';
    if (cell.querySelector('select')) {
      cell.querySelector('select').value = value;
    }
  } else {
    cell.innerHTML = '<input type="' + type + '" name="' + inputName + '" value="' + escapeHtml(value) + '">';
  }
}

/**
 * Escapa HTML especial para evitar XSS
 * @param {string} text - Texto a escapar
 * @returns {string} Texto escapado
 */
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Faz uma requisição AJAX POST
 * @param {FormData} formData - Dados do formulário
 * @returns {Promise} Promise com resposta JSON
 */
async function makeAdminRequest(formData) {
  const response = await fetch('/admin_api.php', {
    method: 'POST',
    body: formData
  });

  if (!response.ok) {
    throw new Error(`HTTP Error: ${response.status}`);
  }

  return await response.json();
}

/**
 * Listener para editar/deletar/salvar empresas
 */
document.addEventListener('click', async function(event) {
  const csrfToken = getCsrfToken();

  // ===== DELETAR EMPRESA =====
  if (event.target.matches('.delete-company')) {
    const tr = event.target.closest('tr');
    const id = tr.dataset.id;

    if (!confirm('Confirma exclusão da empresa ID ' + id + '?')) return;

    const fd = new FormData();
    fd.append('action', 'delete_company');
    fd.append('id', id);
    fd.append('csrf_token', csrfToken);

    try {
      const json = await makeAdminRequest(fd);
      if (json.status === 'ok') {
        tr.remove();
      } else {
        alert('Erro ao excluir: ' + (json.message || 'Desconhecido'));
      }
    } catch (err) {
      alert('Erro na requisição: ' + err.message);
    }
  }

  // ===== EDITAR EMPRESA =====
  if (event.target.matches('.edit-company')) {
    const tr = event.target.closest('tr');
    const name = tr.querySelector('.col-name').textContent.trim();
    const address = tr.querySelector('.col-address').textContent.trim();
    const phone = tr.querySelector('.col-phone').textContent.trim();

    // Transformar células em inputs
    convertCellToInput(tr.querySelector('.col-name'), 'company_name', name);
    convertCellToInput(tr.querySelector('.col-address'), 'address', address);
    convertCellToInput(tr.querySelector('.col-phone'), 'phone', phone);

    // Mudar botão
    event.target.textContent = 'Salvar';
    event.target.classList.remove('edit-company');
    event.target.classList.add('save-company');
  }

  // ===== SALVAR EMPRESA =====
  if (event.target.matches('.save-company')) {
    const tr = event.target.closest('tr');
    const id = tr.dataset.id;
    const company_name = tr.querySelector('input[name="company_name"]').value;
    const address = tr.querySelector('input[name="address"]').value;
    const phone = tr.querySelector('input[name="phone"]').value;

    const fd = new FormData();
    fd.append('action', 'update_company');
    fd.append('id', id);
    fd.append('company_name', company_name);
    fd.append('address', address);
    fd.append('phone', phone);
    fd.append('csrf_token', csrfToken);

    try {
      const json = await makeAdminRequest(fd);
      if (json.status === 'ok') {
        // Atualizar células com novos valores
        tr.querySelector('.col-name').textContent = company_name;
        tr.querySelector('.col-address').textContent = address;
        tr.querySelector('.col-phone').textContent = phone;

        // Voltar botão ao estado normal
        event.target.textContent = 'Editar';
        event.target.classList.remove('save-company');
        event.target.classList.add('edit-company');
      } else {
        alert('Erro ao salvar: ' + (json.message || 'Desconhecido'));
      }
    } catch (err) {
      alert('Erro na requisição: ' + err.message);
    }
  }

  // ===== DELETAR USUÁRIO =====
  if (event.target.matches('.delete-user')) {
    const tr = event.target.closest('tr');
    const id = tr.dataset.id;

    if (!confirm('Confirma exclusão do usuário ID ' + id + '?')) return;

    const fd = new FormData();
    fd.append('action', 'delete_user');
    fd.append('id', id);
    fd.append('csrf_token', csrfToken);

    try {
      const json = await makeAdminRequest(fd);
      if (json.status === 'ok') {
        tr.remove();
      } else {
        alert('Erro ao excluir: ' + (json.message || 'Desconhecido'));
      }
    } catch (err) {
      alert('Erro na requisição: ' + err.message);
    }
  }

  // ===== EDITAR USUÁRIO =====
  if (event.target.matches('.edit-user')) {
    const tr = event.target.closest('tr');
    const name = tr.querySelector('.col-name').textContent.trim();
    const role = tr.querySelector('.col-role').textContent.trim();
    const company = tr.querySelector('.col-company').textContent.trim();

    // Transformar células em inputs
    convertCellToInput(tr.querySelector('.col-name'), 'name', name);
    convertCellToInput(tr.querySelector('.col-role'), 'role', role, 'select');
    convertCellToInput(tr.querySelector('.col-company'), 'company_name', company);

    // Mudar botão
    event.target.textContent = 'Salvar';
    event.target.classList.remove('edit-user');
    event.target.classList.add('save-user');
  }

  // ===== SALVAR USUÁRIO =====
  if (event.target.matches('.save-user')) {
    const tr = event.target.closest('tr');
    const id = tr.dataset.id;
    const name = tr.querySelector('input[name="name"]').value;
    const role = tr.querySelector('select[name="role"]').value;
    const company_name = tr.querySelector('input[name="company_name"]').value;

    const fd = new FormData();
    fd.append('action', 'update_user');
    fd.append('id', id);
    fd.append('name', name);
    fd.append('role', role);
    fd.append('company_name', company_name);
    fd.append('csrf_token', csrfToken);

    try {
      const json = await makeAdminRequest(fd);
      if (json.status === 'ok') {
        // Atualizar células com novos valores
        tr.querySelector('.col-name').textContent = name;
        tr.querySelector('.col-role').textContent = role;
        tr.querySelector('.col-company').textContent = company_name;

        // Voltar botão ao estado normal
        event.target.textContent = 'Editar';
        event.target.classList.remove('save-user');
        event.target.classList.add('edit-user');
      } else {
        alert('Erro ao salvar: ' + (json.message || 'Desconhecido'));
      }
    } catch (err) {
      alert('Erro na requisição: ' + err.message);
    }
  }
});
