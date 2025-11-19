<?php
// API simples para ações administrativas via AJAX
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/csrf.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// requisição só POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','message'=>'Método não permitido']);
    exit;
}

// simples checagem de login
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Não autenticado']);
    exit;
}

// Verificar se o usuário é empresa (admin)
if ($_SESSION['user']['role'] !== 'empresa') {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Acesso negado. Apenas empresas podem acessar o painel admin.']);
    exit;
}

// Validar CSRF token
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Token de segurança inválido']);
    exit;
}

$action = $_POST['action'] ?? '';
try {
    if ($action === 'delete_company') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('ID inválido');
        $ok = delete_company($id);
        echo json_encode(['status' => $ok ? 'ok' : 'error']);
        exit;
    }
    if ($action === 'update_company') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['company_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($id <= 0 || $name === '') throw new Exception('Dados inválidos');
        $ok = update_company($id, $name, $address, $phone);
        echo json_encode(['status' => $ok ? 'ok' : 'error']);
        exit;
    }
    if ($action === 'delete_user') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('ID inválido');
        $ok = delete_user($id);
        echo json_encode(['status' => $ok ? 'ok' : 'error']);
        exit;
    }
    if ($action === 'update_user') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? 'visitante');
        $company_name = trim($_POST['company_name'] ?? '');
        if ($id <= 0 || $name === '') throw new Exception('Dados inválidos');
        $ok = update_user($id, $name, $role, $company_name);
        echo json_encode(['status' => $ok ? 'ok' : 'error']);
        exit;
    }
    throw new Exception('Ação desconhecida');
} catch (Exception $ex) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$ex->getMessage()]);
    exit;
}
