<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'É necessário entrar para gerenciar a agenda.']);
    exit();
}

$idUsuario = (int) $_SESSION['id_usuario'];
$acao = $_POST['acao'] ?? 'listar';

function responder(array $dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit();
}

function dadosConsulta(PDO $pdo, int $idUsuario): array
{
    $stmt = $pdo->prepare(
        'SELECT id_consulta, tipo, especialidade, medico, nome_local, data, horario
         FROM consultas
         WHERE id_usuario = :id_usuario
         ORDER BY data ASC, horario ASC, id_consulta ASC'
    );
    $stmt->execute([':id_usuario' => $idUsuario]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    if ($acao === 'listar') {
        responder(['consultas' => dadosConsulta($pdo, $idUsuario)]);
    }

    if ($acao === 'excluir') {
        $idConsulta = filter_input(INPUT_POST, 'id_consulta', FILTER_VALIDATE_INT);
        if (!$idConsulta) {
            responder(['erro' => 'Consulta inválida.'], 422);
        }

        $stmt = $pdo->prepare(
            'DELETE FROM consultas
             WHERE id_consulta = :id_consulta AND id_usuario = :id_usuario'
        );
        $stmt->execute([
            ':id_consulta' => $idConsulta,
            ':id_usuario' => $idUsuario,
        ]);

        if ($stmt->rowCount() === 0) {
            responder(['erro' => 'Consulta não encontrada.'], 404);
        }

        responder(['consultas' => dadosConsulta($pdo, $idUsuario)]);
    }

    if ($acao !== 'salvar') {
        responder(['erro' => 'Ação inválida.'], 422);
    }

    $idConsulta = filter_input(INPUT_POST, 'id_consulta', FILTER_VALIDATE_INT) ?: null;
    $tipo = trim($_POST['tipo'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');
    $medico = trim($_POST['medico'] ?? '');
    $local = trim($_POST['nome_local'] ?? '');
    $data = trim($_POST['data'] ?? '');
    $horario = trim($_POST['horario'] ?? '');

    if (!in_array($tipo, ['Consulta', 'Exame'], true) || $especialidade === '' || $data === '' || $horario === '') {
        responder(['erro' => 'Preencha tipo, especialidade, data e horário.'], 422);
    }

    $dataValida = DateTime::createFromFormat('Y-m-d', $data);
    $horarioValido = DateTime::createFromFormat('H:i', $horario);
    if (!$dataValida || $dataValida->format('Y-m-d') !== $data || !$horarioValido || $horarioValido->format('H:i') !== $horario) {
        responder(['erro' => 'Informe uma data e um horário válidos.'], 422);
    }

    $dados = [
        ':tipo' => $tipo,
        ':especialidade' => $especialidade,
        ':medico' => $medico,
        ':nome_local' => $local,
        ':data' => $data,
        ':horario' => $horario,
        ':id_usuario' => $idUsuario,
    ];

    if ($idConsulta) {
        $stmt = $pdo->prepare(
            'UPDATE consultas
             SET tipo = :tipo, especialidade = :especialidade, medico = :medico,
                 nome_local = :nome_local, data = :data, horario = :horario
             WHERE id_consulta = :id_consulta AND id_usuario = :id_usuario'
        );
        $dados[':id_consulta'] = $idConsulta;
        $stmt->execute($dados);

        $stmt = $pdo->prepare(
            'SELECT id_consulta
             FROM consultas
             WHERE id_consulta = :id_consulta AND id_usuario = :id_usuario'
        );
        $stmt->execute([
            ':id_consulta' => $idConsulta,
            ':id_usuario' => $idUsuario,
        ]);
        if (!$stmt->fetchColumn()) {
            responder(['erro' => 'Consulta não encontrada ou sem alterações.'], 404);
        }
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO consultas (id_usuario, tipo, especialidade, medico, nome_local, data, horario)
             VALUES (:id_usuario, :tipo, :especialidade, :medico, :nome_local, :data, :horario)'
        );
        $stmt->execute($dados);
    }

    responder(['consultas' => dadosConsulta($pdo, $idUsuario)]);
} catch (PDOException $e) {
    responder(['erro' => 'Não foi possível atualizar a agenda.'], 500);
}
