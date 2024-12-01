<?php 
require 'config.php'; // Asegúrate de incluir la configuración de la base de datos
session_start();

// Verifica si la sesión está activa
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirigir a login si no hay sesión activa
    exit;
}

// Conexión a la base de datos
$id_usuario = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo_ingresado = $_POST['codigo'];

    // Verifica que exista el código de verificación en la sesión
    if (!isset($_SESSION['codigo_verificacion'])) {
        die("No se ha generado ningún código de verificación. Por favor, intenta registrarte nuevamente.");
    }

    $codigo_verificacion = $_SESSION['codigo_verificacion'];

    // Verificar si el código ingresado es correcto
    if ($codigo_ingresado == $codigo_verificacion) {
        // Código correcto, eliminar la cuenta
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);

        // Destruir la sesión y redirigir
        session_unset();
        session_destroy();
        header("Location: register.php"); // Redirigir a register.php
        exit();
    } else {
        echo "El código de verificación es incorrecto. Por favor, intenta nuevamente.";
    }
}
?>
