<?php
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "u524804893_sena";
$password = "Mercadosena23";
$dbname = "u524804893_market";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo_ingresado = $_POST['codigo'];

    // Verifica que exista el código de verificación en la sesión
    if (!isset($_SESSION['codigo_verificacion'])) {
        die("No se ha generado ningún código de verificación. Por favor, intenta registrarte nuevamente.");
    }

    $codigo_verificacion = $_SESSION['codigo_verificacion'];

    // Verificar si el código ingresado es correcto
    if ($codigo_ingresado == $codigo_verificacion) {
        // Código verificado correctamente
        // Redirigir a la página cambiarcontraseña.php
        header("Location: cambiarcontraseña.php");
        exit();
    } else {
        $error_message = "El código de verificación es incorrecto. Por favor, intenta nuevamente.";
    }
}

$conn->close();
?>

<!-- HTML para el formulario de verificación -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación - Market Sena</title>
    <link rel="icon" href="logo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif; /* Fuente atractiva */
            background-color: #001f3f; /* Azul oscuro */
            color: white; /* Texto blanco para el título */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* 100% del alto de la ventana */
            margin: 0;
            overflow: hidden; /* Fija la pantalla para que no se pueda mover hacia abajo */
            flex-direction: column; /* Alinear en columna */
        }
        .form-container {
            background-color: rgba(255, 255, 255, 0.8); /* Fondo blanco semi-transparente */
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            max-width: 400px; /* Ancho ajustado */
            width: 100%; /* Ancho responsivo */
            margin-top: 20px; /* Espacio arriba del formulario */
        }
        .form-title {
            color: white; /* Título en blanco */
            text-align: center; /* Centrar el título */
            margin-bottom: 10px; /* Espacio debajo del título */
        }
        label {
            color: black; /* Color negro para los labels */
        }
        .error-message {
            color: red; /* Color rojo para los mensajes de error */
            text-align: center; /* Centrar el mensaje */
            margin-top: 10px; /* Espacio arriba del mensaje */
        }
        .info-message {
            color: white; /* Color negro para el mensaje informativo */
            text-align: center; /* Centrar el mensaje */
            margin-bottom: 20px; /* Espacio debajo del mensaje */
            display: inline; /* Mostrar en una sola fila */
        }
    </style>
</head>
<body>
    <h1 class="form-title">Market-Sena Restablecer Contraseña</h1>
    <div class="info-message">
        <p>Se ha enviado un código de verificación a su correo. Por favor, ingrese el código a continuación:</p>
    </div>
    <div class="form-container">
        <form method="post">
            <div class="mb-3">
                <label for="codigo" class="form-label">Ingrese el Código de Verificación para Restablecer su contraseña:</label>
                <input type="text" name="codigo" class="form-control" required placeholder="Escriba el codigo porfavor">
            </div>
            <button type="submit" class="btn btn-primary">Confirmar</button>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
