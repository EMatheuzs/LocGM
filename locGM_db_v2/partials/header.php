<?php
require_once __DIR__ . '/../data.php'; require_login(); $user=current_user();
?><!DOCTYPE html><html lang="pt-br"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>locGM</title><link rel="stylesheet" href="/static/style.css"><link rel="icon" href="data:,"></head>
<body>
<header class="topbar">
  <div class="brand">locGM</div>
  <nav class="menu">
    <a href="/home.php">Início</a>
    <a href="/map.php">Mapa</a>
    <a href="/feed.php">Social</a>
    <a href="/chat.php">Chat</a>
    <a href="/emergencias.php">Emergências</a>
    <a href="/profile.php">Perfil</a>
    <?php if($user['role']==='empresa'): ?>
      <a href="/empresa.php">Empresa</a>
      <a href="/admin_companies.php">Empresas (DB)</a>
      <a href="/admin_users.php">Usuários (DB)</a>
    <?php endif; ?>
    <a href="/logout.php">Sair</a>
  </nav>
</header>
<main class="container">