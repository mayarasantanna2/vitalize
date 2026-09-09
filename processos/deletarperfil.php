<?php
session_start();

require_once __DIR__ . '/../conexao.php';

if (empty($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

$id_usuario = (int) $_SESSION['id_usuario'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('DELETE FROM consultas WHERE id_usuario = :id_usuario');
    $stmt->execute([':id_usuario' => $id_usuario]);

    $stmt = $pdo->prepare('DELETE FROM relatos WHERE id_usuario = :id_usuario');
    $stmt->execute([':id_usuario' => $id_usuario]);

    $stmt = $pdo->prepare('DELETE FROM grupos WHERE id_criador = :id_usuario');
    $stmt->execute([':id_usuario' => $id_usuario]);

    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id_usuario');
    $stmt->execute([':id_usuario' => $id_usuario]);
    $pdo->commit();

    session_unset();
    session_destroy();

    header('Location: ../login.php');
    exit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['erro_perfil'] = 'Não foi possível excluir a conta.';
    header('Location: ../pagperfil.php');
    exit();
}
