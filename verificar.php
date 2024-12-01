<?php
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = ""; //tu usuario
$password = ""; // tu contraseña
$dbname = "MarketSenadb";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Comprobamos que el código de verificación existe en la sesión
if (!isset($_SESSION['codigo_verificacion']) || !isset($_SESSION['correo'])) {
    die("No se ha generado ningún código de verificación o la sesión ha expirado. Por favor, intenta registrarte nuevamente.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo_ingresado = $_POST['codigo'];

    // Verificar si el código ingresado es correcto
    if ($codigo_ingresado == $_SESSION['codigo_verificacion']) {
        // Obtener el correo de la sesión
        $correo_usuario = $_SESSION['correo'];

        // Actualizar el estado de verificación en la base de datos
        $sql = "UPDATE usuarios SET Verificado = 1 WHERE Correo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $correo_usuario);

        if ($stmt->execute()) {
            // Si la verificación es exitosa, redirigir a la página de inicio de sesión
            unset($_SESSION['codigo_verificacion']); // Eliminar el código de verificación de la sesión
            unset($_SESSION['correo']); // Eliminar el correo de la sesión
            header("Location: login.php?success-register=1");
            exit();
        } else {
            $error_message = "Hubo un problema al actualizar el estado de verificación. Intenta nuevamente.";
        }
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
            font-family: 'Montserrat', sans-serif;
            background-color: #001f3f;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            flex-direction: column;
        }
        .form-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            width: 100%;
            margin-top: 10px;
        }
        .form-title {
            color: white;
            text-align: center;
            margin-bottom: 10px;
        }
        label {
            color: black;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
        .info-message {
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1 class="form-title">Bienvenido a Market-Sena</h1>
    <div class="info-message">
        <p>Se ha enviado un código de verificación a su correo. Por favor, ingrese el código a continuación:</p>
    </div>
    <div class="form-container">
        <form method="post">
            <div class="mb-3">
                <label for="codigo" class="form-label">Ingrese el Código de Verificación:</label>
                <input type="text" name="codigo" class="form-control" required placeholder="Porfavor, escriba el codigo">
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

