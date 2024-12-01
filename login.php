<?php
require 'config.php'; // Asegúrate de que 'config.php' establezca la conexión PDO correctamente
session_start();

// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'];
    $contraseña = $_POST['contraseña'];

    // Consultar usuario en la base de datos
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE Correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // El usuario está registrado, verificar la contraseña
        if (password_verify($contraseña, $usuario['Contrasena'])) { // Verifica la contraseña
            $_SESSION['user_id'] = $usuario['id_usuario']; // Almacena el ID del usuario en la sesión
            $_SESSION['nombre_usuario'] = $usuario['Nombre']; // Almacena el nombre del usuario en la sesión
            $_SESSION['rol'] = $usuario['Rol']; // Asegúrate de que esto esté configurado correctamente
            header('Location: paginainicial.php'); // Redirige a la página inicial
            exit;
        } else {
            echo "<script>alert('Correo o contraseña incorrectos');</script>";
        }
    } else {
        // Usuario no encontrado
        echo "<script>alert('Este usuario no está registrado en MarketSena');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketSena - Inicio de Sesión</title>
    <link rel="icon" href="logo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #001f3f;
            /* Azul oscuro */
            color: white;
            /* Texto blanco para el título */
            display: flex;
            flex-direction: column;
            /* Apilar elementos verticalmente */
            justify-content: center;
            /* Centrar verticalmente */
            align-items: center;
            /* Centrar horizontalmente */
            height: 100vh;
            /* 100% del alto de la ventana */
            margin: 0;
        }

        .form-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            /* Ajusta el ancho del formulario */
            width: 100%;
            margin-top: 20px;
            /* Espacio entre el título y el formulario */
        }

        .form-title {
            color: white;
            text-align: center;
            margin-bottom: 20px;
            /* Espacio adecuado para el título */
        }

        label {
            color: black;
        }

        .btn-login {
            margin-bottom: 3%;
            display: block;
            /* Hacer que el botón esté en una línea separada */
        }
    </style>
</head>

<body>
    <h1 class="form-title">Iniciar Sesión en MarketSena</h1>
    
    <?php if (isset($_GET['success-register']) && $_GET['success-register'] == 1): ?>
    <div class="align-items-center">
        <div class="alert alert-success text-center mt-1" role="alert">
            ¡Cuenta creada con éxito! Porfavor, inicie sesión.
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success-delete']) && $_GET['success-delete'] == 1): ?>
    <div class="align-items-center">
        <div class="alert alert-success text-center mt-1" role="alert">
            ¡Cuenta eliminada con éxito! Hasta pronto.
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="align-items-center">
        <div class="alert alert-success text-center mt-1" role="alert">
            Contraseña actualizada con exito, porfavor inicie sesión de nuevo.
        </div>
    </div>
    <?php endif; ?>
    
    <div class="form-container">
        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" class="form-control" required placeholder="Ingrese su Correo Registrado">
            </div>
            <div class="mb-3">
                <label for="contraseña" class="form-label">Contraseña</label>
                <input type="password" name="contraseña" class="form-control" required placeholder="Ingrese su Contraseña">
            </div>
            <button type="submit" class="btn btn-primary btn-login">Iniciar Sesión</button>
            <p style="color: black;"><a href="recuperar.php" style="color: #007bff;">¿Olvidaste tu contraseña?</a></p>
            <p style="color: black;">¿No tienes una cuenta? <a href="index.php" style="color: #007bff;">Regístrate aquí</a></p>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>