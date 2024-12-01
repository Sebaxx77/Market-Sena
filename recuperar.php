<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require ''; //Ruta al autoloader de composer

session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "";//tu usuario
$password = "";//tu contraseña
$dbname = "MarketSenadb";//Nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];

    // Verifica si el correo existe en la base de datos
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE Correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // Generar código de verificación
        $codigo_verificacion = rand(100000, 999999); // Código de 6 dígitos
        $_SESSION['codigo_verificacion'] = $codigo_verificacion;
        $_SESSION['correo'] = $correo; // Guardar el correo en la sesión para usarlo después

        // Enviar correo
        $mail = new PHPMailer(true);
        try {
            // Configura el servidor SMTP
            $mail->isSMTP();
            $mail->Host = ''; // Usar el servidor SMTP de composer
            $mail->SMTPAuth = true;
            $mail->Username = ''; // Tu correo
            $mail->Password = ''; // Contraseña
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            // Destinatarios
            $mail->setFrom('marketsena@marketsena.shop', 'MARKET-SENA'); //modificar correo
            $mail->addAddress($correo); // Agregar destinatario

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Restablecer datos en Market-Sena';
            $mail->Body = "Tu código de verificación es: <strong>$codigo_verificacion</strong>";
            $mail->AltBody = "Tu código de verificación es: $codigo_verificacion";

            $mail->send();

            // Redirigir a la página de verificación
            header("Location: verificar1.php"); // Cambia esto a tu archivo de verificación
            exit();
        } catch (Exception $e) {
            echo "El correo no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "<script>alert('El correo no está registrado.');</script>";
    }
}

$conn->close();
?>

<!-- HTML para el formulario de recuperación -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Market Sena</title>
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
            margin-top: 20px;
        }

        .form-title {
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            color: black;
        }

        .btn-send {
            margin-bottom: 3%;
        }
    </style>
</head>

<body>
    <h1 class="form-title">Restablecer Contraseña Market-Sena</h1>
    <div class="form-container">
        <form method="post">
            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" class="form-control" required placeholder="Ingrese su Correo Registrado">
            </div>
            <button type="submit" class="btn btn-primary btn-send">Enviar Código de Verificación</button>
            <p style="color: black;">¿Ya tienes una cuenta? <a href="login.php" style="color: #007bff;">Iniciar sesión</a></p>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
