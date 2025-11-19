# locGM — Sistema de Localização Geográfica para Empresas

## 📋 Sobre o Projeto

**locGM** é uma aplicação web full-stack desenvolvida para fins educacionais que permite:

- **Visitantes** descobrirem empresas e locais de interesse em Guajará-Mirim (RO) através de um mapa interativo
- **Empresas** gerenciarem seus locais, publicar promoções e se comunicar com visitantes  
- **Administradores** gerenciarem usuários e empresas via painel administrativo

**Contexto:** Projeto de conclusão — 3º ano Ensino Médio, demonstrando conhecimentos em web development full-stack.

**Tecnologias utilizadas:**
- **Frontend:** HTML5, CSS3 (responsivo), JavaScript (ES6+), Leaflet.js (mapas)
- **Backend:** PHP 7.4+, MySQL 5.7+
- **Padrões:** MVC simplificado, API REST via AJAX, Prepared Statements (SQL injection prevention)

---

## 🗂️ Estrutura do Projeto

```
locGM_db_v2/
├── index.php              # Login/Tela inicial
├── home.php               # Página inicial (após login)
├── map.php                # Mapa interativo com Leaflet
├── profile.php            # Perfil do usuário
├── empresa.php            # Painel da empresa (criar/editar locais)
├── feed.php               # Feed social (promoções)
├── chat.php               # Chat entre visitantes e empresas
├── emergencias.php        # Telefones de emergência
├── logout.php             # Sair
│
├── admin_companies.php    # Painel admin: listar/editar/excluir empresas
├── admin_users.php        # Painel admin: listar/editar/excluir usuários
├── admin_api.php          # API AJAX para operações admin
│
├── partials/
│   ├── header.php         # Cabeçalho (menu principal)
│   └── footer.php         # Rodapé
│
├── static/
│   └── style.css          # Estilos globais
│
├── data.php               # Funções de dados (sessão)
├── db.php                 # Conexão e operações MySQL
│
├── BD.sql                 # Script de criação do banco de dados
├── import_db.php          # Importador de BD.sql
└── README.md              # Este arquivo
```

---

## 🚀 Como Instalar e Rodar

### Pré-requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior (XAMPP recomendado)
- Navegador moderno (Chrome, Firefox, Edge)

### Passo 1: Preparar o banco de dados

# recomendado para abri o site
**Via PHP CLI:**
```bash
cd /caminho/para/locGM_db_v2
php -S 127.0.0.1:8000
# Acesse: http://localhost:8000
```

**Opção A: Usando CLI MySQL**
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS locGM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p locGM < BD.sql
```

**Opção B: Usando PHP CLI**
```bash
cd /caminho/para/locGM_db_v2
php import_db.php
```

**Opção C: Usando phpMyAdmin**
1. Abra `http://localhost/phpmyadmin/`
2. Crie banco `locGM`
3. Importe o arquivo `BD.sql` via "Importar"

### Passo 2: Rodar a aplicação

**Via XAMPP (recomendado):**
1. Coloque a pasta `locGM_db_v2` em `C:\xampp\htdocs\`
2. Abra `http://localhost/locGM_db_v2/`

---

## 📖 Guia de Uso

### 👤 Para Visitantes

1. **Página Inicial** — Veja locais em destaque (filtre por categoria)
2. **Mapa** — Visualize todos os locais no mapa interativo; traçar rotas até locais
3. **Social** — Acompanhe promoções das empresas
4. **Chat** — Converse com empresas para dúvidas
5. **Emergências** — Acesse telefones úteis
6. **Perfil** — Edite seus dados pessoais

### 🏢 Para Empresas

1. **Painel Empresa** — Gerencie seus locais:
   - Adicione novos locais via formulário ou **clicando no mapa**
   - Edite/delete locais existentes
   
2. **Mapa** — Clique em qualquer ponto para criar novo local:
   - O marcador é arrastável para ajustar a posição
   - Preencha nome, tipo, nota e endereço
   - Clique "Salvar" para confirmar

3. **Social** — Publique promoções que aparecem no feed de visitantes

4. **Painéis Admin** — Acesso a:
   - **Empresas (DB):** listar/editar/deletar empresas cadastradas
   - **Usuários (DB):** listar/editar/deletar usuários do sistema
   - Edições são feitas inline via AJAX

---

## 🔒 Segurança

### Implementado:
- ✅ **Prepared Statements** em todas as queries (proteção contra SQL Injection)
- ✅ **Validação de dados** no servidor (não confiar apenas no cliente)
- ✅ **Autenticação** via sessão PHP
- ✅ **Tokens CSRF** em formulários
- ✅ **Sanitização de output** com `htmlspecialchars()`
- ✅ **Fallback gracioso** para sessão se banco indisponível

### Não implementado (fora do escopo):
- ❌ Autenticação real (OAuth, bcrypt)
- ❌ Rate limiting (proteção contra brute force)
- ❌ HTTPS (apenas HTTP local)

---

## 🧪 Testando as Funcionalidades

### Teste 1: Login e Persistência
1. Acesse `/index.php`
2. Selecione "Empresa" e insira um e-mail qualquer
3. Após login, vá a "Empresas (DB)" — seu e-mail deve aparecer na lista
4. Saia (`/logout.php`) — o registro permanece no banco

### Teste 2: Criar Local no Mapa (Empresa)
1. Faça login como empresa
2. Clique em "Mapa"
3. Clique em qualquer ponto do mapa
4. Preencha nome, tipo, nota (0-5) e endereço
5. Clique "Salvar" — marcador aparece sem recarregar
6. Verifique no "Painel Empresa" que foi salvo

### Teste 3: Editar/Deletar Usuários
1. Faça login como empresa
2. Vá a "Usuários (DB)"
3. Clique "Editar" — campos viram inputs inline
4. Modifique dados e clique "Salvar"
5. Teste "Excluir" com confirmação (usa AJAX)

### Teste 4: Validação em Tempo Real
1. Vá ao mapa e tente criar local com:
   - Nome vazio
   - Latitude > 90 ou < -90
   - Nota > 5
2. Você vê mensagens de erro vermelhas sem recarregar

---

## 🐛 Troubleshooting

| Problema | Solução |
|----------|---------|
| Erro "Não foi possível conectar ao banco" | Verifique se MySQL está rodando em `localhost:3306` |
| Dados não salvam | Confirme que banco `locGM` foi criado; execute `import_db.php` |
| Editar usuários não funciona | Abra console do navegador (F12) e procure por erros de rede |
| Mapa não carrega | Confirme que há conexão com internet (Leaflet é CDN) |
| Geolocalização não funciona | Ative permissão no navegador (canto superior direito) |

---

## 📊 Conceitos Demonstrados

| Categoria | Conceitos |
|-----------|-----------|
| **Backend** | PHP OOP, funções reutilizáveis, conexão MySQL, prepared statements |
| **Frontend** | DOM manipulation, event listeners, Fetch API, validação de forms |
| **Database** | Schema design, relacionamentos, CRUD operations, índices |
| **Segurança** | SQL injection prevention, XSS mitigation, CSRF tokens, session management |
| **API** | REST via AJAX, JSON, status codes HTTP, error handling |
| **Design** | CSS Grid/Flexbox, variáveis CSS, design responsivo, mobile-first |

---

## 🎯 Fluxo de Demonstração Recomendado

1. **Login como visitante** → browse mapa e ver locais de empresas
2. **Login como empresa** → criar novo local clicando no mapa (sem reload)
3. **Painel admin** → editar/deletar usuários via AJAX com confirmação
4. **Validação** → tentar salvar dados inválidos e ver erros em tempo real
5. **Responsividade** → redimensionar janela e ver layout se adaptar
6. **Performance** → demonstrar que edições acontecem sem recarregar página

---

## 📱 Suporte a Navegadores

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Edge 90+
- ✅ Safari 14+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

---

**Desenvolvido para fins educacionais — 2025**
**Projeto de Conclusão 3º Ano Ensino Médio**
