<?php
// Script simples para importar o arquivo `BD.sql` em um servidor MySQL local.
// Uso CLI: php import_db.php
// Ajuste as credenciais conforme necessário (XAMPP padrão: user=root, senha="").

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$port = 3306;

$sqlFile = __DIR__ . '/BD.sql';
if (!file_exists($sqlFile)) {
    echo "Arquivo BD.sql não encontrado em: $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
if (trim($sql) === '') {
    echo "O arquivo BD.sql está vazio. Verifique o conteúdo.\n";
    exit(1);
}

$mysqli = new mysqli($host, $user, $pass, null, $port);
if ($mysqli->connect_errno) {
    echo "Falha ao conectar MySQL: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}

// Ativa múltiplas consultas e executa
if (!$mysqli->multi_query($sql)) {
    echo "Erro ao executar script SQL: ({$mysqli->errno}) {$mysqli->error}\n";
    $mysqli->close();
    exit(1);
}

echo "Execução iniciada...\n";
// Consome todos os resultados para garantir execução completa
do {
    if ($res = $mysqli->store_result()) {
        $res->free();
    } else {
        if ($mysqli->errno) {
            echo "Erro em passo: ({$mysqli->errno}) {$mysqli->error}\n";
        }
    }
} while ($mysqli->more_results() && $mysqli->next_result());

if ($mysqli->errno) {
    echo "Import concluído com erros: ({$mysqli->errno}) {$mysqli->error}\n";
} else {
    echo "Import concluído com sucesso. Banco 'locGM' criado/populado.\n";
}

$mysqli->close();

?>