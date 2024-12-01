<?php
// Importar el autoloader de Composer
require '/home/u524804893/domains/marketsena.shop/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Inicializar variables para los mensajes
$mensaje_error = null;
$mensaje_confirmacion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $descripcion = trim($_POST['descripcion']);

    // Validar la entrada
    if (empty($nombre) || empty($telefono) || empty($correo) || empty($descripcion)) {
        $mensaje_error = "Todos los campos son obligatorios.";
    } else {
        $mail = new PHPMailer(true);

        try {
            // Configura el servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com'; // Usar el servidor SMTP de Hostinguer
            $mail->SMTPAuth = true;
            $mail->Username = 'marketsena@marketsena.shop'; // Tu correo
            $mail->Password = 'Juanborrero1@'; // Contraseña
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            // Configurar el remitente y destinatario
            $mail->setFrom('marketsena@marketsena.shop', 'Soporte de Market-Sena');
            $mail->addAddress($correo); // Enviar al correo ingresado

            // Generar un ID único para el caso
            $caso_id = rand(1000, 9999);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Soporte-MarketSena recibio tu mensaje.';
            $mail->Body = "
                <h2>Tu caso ha sido recibido</h2>
                <p>Gracias por contactar con el soporte de Market-Sena.</p>
                <p><strong>Nombre:</strong> $nombre</p>
                <p><strong>Teléfono:</strong> $telefono</p>
                <p><strong>Correo:</strong> $correo</p>
                <p><strong>ID del caso:</strong> $caso_id</p>
                <p><strong>Descripción del problema:</strong></p>
                <p>$descripcion</p>
                <p>Te responderemos a la brevedad.</p>
            ";

            // Intentar enviar el correo
            if ($mail->send()) {
                $mensaje_confirmacion = "Tu mensaje ha sido recibido. Nuestro equipo de soporte te responderá pronto. ID de caso: $caso_id.";
            } else {
                $mensaje_error = "No se pudo enviar el correo. Por favor, inténtalo más tarde.";
            }
        } catch (Exception $e) {
            $mensaje_error = "Hubo un problema al enviar el correo: " . $mail->ErrorInfo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - Market Sena</title>
    <link rel="icon" href="logo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #001f3f;
        }

        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 30px auto;
        }

        .form-title {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
            font-size: 1.8rem;
        }

        /* Estilo para el botón "Volver" */
        .back-button {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2 class="form-title"><strong>Formulario de Soporte - MarketSena</strong></h2>

        <!-- Botón de volver dentro del formulario -->
        <div class="back-button">
            <a href="paginainicial.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <?php if (isset($mensaje_error)): ?>
            <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
        <?php elseif (isset($mensaje_confirmacion)): ?>
            <div class="alert alert-success"><?php echo $mensaje_confirmacion; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="nombre" class="form-label"><strong>Nombre Completo:</strong></label>
                <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Ingrese su nombre completo">
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label"><strong>Teléfono Celular:</strong></label>
                <input type="text" class="form-control" id="telefono" name="telefono" required placeholder="Ingrese su número de teléfono">
            </div>
            <div class="mb-3">
                <label for="correo" class="form-label"><strong>Correo de Contacto:</strong></label>
                <input type="email" class="form-control" id="correo" name="correo" required placeholder="Ingrese su correo electrónico">
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label"><strong>Describe claramente tu problema:</strong></label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required placeholder="Escriba una descripción detallada de su problema."></textarea>
            </div>
            <div class="mb-3">
                <label for="evidencia" class="form-label"><strong>Fotos o Evidencias (opcional):</strong></label>
                <input type="file" class="form-control" id="evidencia" name="evidencia">
            </div>
            <button type="submit" class="btn btn-primary">Enviar a soporte</button>
        </form>
    </div>
</body>

</html>