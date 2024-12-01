<?php
require 'config.php';

session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirige a login si no hay sesión activa
    exit;
}

$id_usuario = $_SESSION['user_id'];

// Inicializa la variable para indicar si la contraseña fue cambiada
$contraseña_cambiada = false;

// Verifica si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $documento = $_POST['documento']; // Asumiendo que este campo no se puede editar
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo']; // Asumiendo que este campo no se puede editar
    $direccion = $_POST['direccion'];
    $contrasena_actual = $_POST['contrasena_actual'];
    $nueva_contrasena = $_POST['nueva_contrasena'];

    // Obtener la contraseña actual del usuario
    $stmt = $pdo->prepare("SELECT contraseña FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($contrasena_actual, $usuario['contraseña'])) {
        // Si la contraseña actual es correcta, proceder a cambiarla
        if (!empty($nueva_contrasena)) {
            $hashed_password = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET contraseña = ?, fecha_cambio_contrasena = NOW() WHERE id_usuario = ?");
            $stmt->execute([$hashed_password, $id_usuario]);
            $contraseña_cambiada = true; // Se cambió la contraseña
        }
    } else {
        // Manejar el error de contraseña incorrecta
        echo "<script>alert('La contraseña actual es incorrecta.'); window.history.back();</script>";
        exit;
    }

    // Actualiza otros campos que pueden ser cambiados
    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, telefono = ?, direccion = ? WHERE id_usuario = ?");
    $stmt->execute([$nombre, $telefono, $direccion, $id_usuario]);

    // Si se cambió la contraseña, cierra la sesión
    if ($contraseña_cambiada) {
        session_unset(); // Limpia la sesión
        session_destroy(); // Destruye la sesión
        header('Location: login.php'); // Redirige a login
    } else {
        header('Location: PaginaInicial.php'); // Redirige a página inicial
    }
    exit;
} else {
    // Redirige si no se accede por POST
    header('Location: PaginaInicial.php');
    exit;
}
