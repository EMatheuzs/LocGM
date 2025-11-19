<?php
/**
 * CSRF Token Management
 * Funções para gerar e validar tokens CSRF
 */

// Garantir que a sessão está ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Gera um token CSRF e o armazena na sessão
 */
function generate_csrf_token() {
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

/**
 * Retorna o token CSRF atual
 */
function get_csrf_token() {
  return $_SESSION['csrf_token'] ?? '';
}

/**
 * Valida o token CSRF
 */
function validate_csrf_token($token) {
  if (!isset($_SESSION['csrf_token'])) {
    return false;
  }
  return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Cria um campo hidden HTML com o token CSRF
 */
function csrf_field() {
  return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_csrf_token()) . '">';
}
