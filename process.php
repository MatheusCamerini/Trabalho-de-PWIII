<?php
// ============================================================
//  process.php — Back-end CRUD (Create · Update · Delete)
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once 'config.php';

// ── Helpers ──────────────────────────────────────────────────

/**
 * Sanitiza uma string contra XSS.
 */
function sanitize(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Envia resposta JSON padronizada e encerra.
 */
function respond(bool $success, string $message, array $data = []): never
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Roteamento por ação ──────────────────────────────────────

$action = sanitize($_POST['action'] ?? '');

try {
    $db = getDB();

    match ($action) {
        'create' => actionCreate($db),
        'update' => actionUpdate($db),
        'delete' => actionDelete($db),
        default  => respond(false, 'Ação inválida ou não informada.'),
    };

} catch (PDOException $e) {
    // Nunca expor detalhes do banco ao cliente
    error_log('PDOException: ' . $e->getMessage());
    respond(false, 'Erro interno no banco de dados. Tente novamente.');
}

// ── Ações ────────────────────────────────────────────────────

function actionCreate(PDO $db): never
{
    $data = validateItemPayload();

    $stmt = $db->prepare(
        'INSERT INTO itens (nome, categoria, quantidade, preco)
         VALUES (:nome, :categoria, :quantidade, :preco)'
    );
    $stmt->execute($data);

    $newId = (int) $db->lastInsertId();

    $row = $db->prepare('SELECT * FROM itens WHERE id = :id');
    $row->execute([':id' => $newId]);

    respond(true, 'Item adicionado com sucesso!', $row->fetch());
}

function actionUpdate(PDO $db): never
{
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        respond(false, 'ID inválido para atualização.');
    }

    // Confirma existência
    $check = $db->prepare('SELECT id FROM itens WHERE id = :id');
    $check->execute([':id' => $id]);
    if (!$check->fetch()) {
        respond(false, 'Item não encontrado.');
    }

    $data         = validateItemPayload();
    $data[':id']  = $id;

    $stmt = $db->prepare(
        'UPDATE itens
         SET nome = :nome, categoria = :categoria,
             quantidade = :quantidade, preco = :preco
         WHERE id = :id'
    );
    $stmt->execute($data);

    $row = $db->prepare('SELECT * FROM itens WHERE id = :id');
    $row->execute([':id' => $id]);

    respond(true, 'Item atualizado com sucesso!', $row->fetch());
}

function actionDelete(PDO $db): never
{
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        respond(false, 'ID inválido para exclusão.');
    }

    $check = $db->prepare('SELECT id FROM itens WHERE id = :id');
    $check->execute([':id' => $id]);
    if (!$check->fetch()) {
        respond(false, 'Item não encontrado.');
    }

    $stmt = $db->prepare('DELETE FROM itens WHERE id = :id');
    $stmt->execute([':id' => $id]);

    respond(true, 'Item excluído com sucesso!', [':id' => $id]);
}

// ── Validação e sanitização centralizada ─────────────────────

function validateItemPayload(): array
{
    $nome      = sanitize($_POST['nome']      ?? '');
    $categoria = sanitize($_POST['categoria'] ?? '');
    $qtd       = $_POST['quantidade'] ?? '';
    $preco     = $_POST['preco']      ?? '';

    $errors = [];

    if ($nome === '' || mb_strlen($nome) < 2) {
        $errors[] = 'Nome deve ter ao menos 2 caracteres.';
    }
    if (mb_strlen($nome) > 120) {
        $errors[] = 'Nome não pode ultrapassar 120 caracteres.';
    }
    if ($categoria === '' || mb_strlen($categoria) < 2) {
        $errors[] = 'Categoria deve ter ao menos 2 caracteres.';
    }
    if (mb_strlen($categoria) > 80) {
        $errors[] = 'Categoria não pode ultrapassar 80 caracteres.';
    }

    $quantidade = filter_var($qtd, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($quantidade === false) {
        $errors[] = 'Quantidade deve ser um número inteiro não negativo.';
    }

    $precoFloat = filter_var(str_replace(',', '.', $preco), FILTER_VALIDATE_FLOAT);
    if ($precoFloat === false || $precoFloat < 0) {
        $errors[] = 'Preço deve ser um número decimal não negativo.';
    }

    if (!empty($errors)) {
        respond(false, implode(' | ', $errors));
    }

    return [
        ':nome'       => $nome,
        ':categoria'  => $categoria,
        ':quantidade' => (int) $quantidade,
        ':preco'      => round((float) $precoFloat, 2),
    ];
}
