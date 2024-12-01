<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_ingresado = $_POST['codigo_verificacion'];

    if ($codigo_ingresado == $_SESSION['codigo_verificacion']) {
        // Aquí puedes proceder a eliminar la cuenta
        require 'config.php';
        $id_usuario = $_SESSION['user_id'];

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);

        // Destruye la sesión y redirige a la página de inicio
        session_unset();
        session_destroy();
        header('Location: login.php?success-delete=1'); // Redirige al login después de borrar la cuenta
        exit;
    } else {
        echo "<script>alert('Código incorrecto. Inténtalo de nuevo.');</script>";
    }
}
?>
