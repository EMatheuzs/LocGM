<?php
// Simple MySQL helper for the project. Adjust credentials if needed.
// Usage: require_once __DIR__ . '/db.php'; $db = db_connect();

function db_connect(): ?mysqli {
    static $conn = null;
    if ($conn !== null) return $conn;
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $name = getenv('DB_NAME') ?: 'locGM';
    $port = getenv('DB_PORT') ?: 3306;

    $conn = new mysqli($host, $user, $pass, $name, (int)$port);
    if ($conn->connect_errno) {
        // return null on failure; the app will gracefully fallback to session-based seed
        error_log("DB connect error: ({$conn->connect_errno}) {$conn->connect_error}");
        $conn = null;
        return null;
    }
    // ensure minimal schema exists
    $sql = "CREATE TABLE IF NOT EXISTS `companies` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `company_name` VARCHAR(255) NOT NULL,
        `address` VARCHAR(255),
        `phone` VARCHAR(50)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($sql)) {
        error_log('Failed to ensure companies table: ' . $conn->error);
    }
    // ensure users table exists
    $sql2 = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `role` VARCHAR(20) NOT NULL DEFAULT 'visitante',
        `name` VARCHAR(255),
        `company_name` VARCHAR(255),
        `phone` VARCHAR(50),
        `address` VARCHAR(255),
        `prices_note` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($sql2)) {
        error_log('Failed to ensure users table: ' . $conn->error);
    }
    return $conn;
}

function company_exists(string $email): bool {
    $db = db_connect();
    if (!$db) return false;
    $stmt = $db->prepare('SELECT id FROM companies WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function create_company(string $email, string $company_name = ''): ?int {
    $db = db_connect();
    if (!$db) return null;
    $stmt = $db->prepare('INSERT INTO companies (email, company_name) VALUES (?, ?)');
    $stmt->bind_param('ss', $email, $company_name);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $id = $stmt->insert_id;
    $stmt->close();
    return $id ?: null;
}

function get_companies_from_db(): array {
    $db = db_connect();
    if (!$db) return [];
    $res = $db->query('SELECT id, email, company_name, address, phone FROM companies');
    if (!$res) return [];
    $rows = [];
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    $res->free();
    return $rows;
}

function user_exists(string $email): bool {
    $db = db_connect();
    if (!$db) return false;
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function create_user(string $email, string $role = 'visitante', string $name = '', string $company_name = ''): ?int {
    $db = db_connect();
    if (!$db) return null;
    $stmt = $db->prepare('INSERT INTO users (email, role, name, company_name) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $email, $role, $name, $company_name);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $id = $stmt->insert_id;
    $stmt->close();
    return $id ?: null;
}

function get_users_from_db(): array {
    $db = db_connect();
    if (!$db) return [];
    $res = $db->query('SELECT id, email, role, name, company_name, phone, address FROM users');
    if (!$res) return [];
    $rows = [];
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    $res->free();
    return $rows;
}

function delete_company(int $id): bool {
    $db = db_connect();
    if (!$db) return false;
    $stmt = $db->prepare('DELETE FROM companies WHERE id = ?');
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function update_company(int $id, string $company_name, string $address = '', string $phone = ''): bool {
    $db = db_connect();
    if (!$db) return false;
    $stmt = $db->prepare('UPDATE companies SET company_name = ?, address = ?, phone = ? WHERE id = ?');
    $stmt->bind_param('sssi', $company_name, $address, $phone, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function delete_user(int $id): bool {
    $db = db_connect();
    if (!$db) return false;
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function update_user(int $id, string $name, string $role = 'visitante', string $company_name = ''): bool {
    $db = db_connect();
    if (!$db) return false;
    $stmt = $db->prepare('UPDATE users SET name = ?, role = ?, company_name = ? WHERE id = ?');
    $stmt->bind_param('sssi', $name, $role, $company_name, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

?>
