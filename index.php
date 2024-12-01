<?php
// Importar el autoloader de Composer
require 'Ruta al autoloader de Composer';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Iniciar la sesión para almacenar el código de verificación
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = ""; //tu usuario
$password = ""; //tu contraseña
$dbname = "MarketSenadb"; //nombre de la base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$mensaje_error = ''; // Variable para mostrar el error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recolectar datos del formulario
    $nombre = $_POST['nombre'];
    $documento = $_POST['documento'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $correo = $_POST['correo'];
    $rol = $_POST['rol'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $mensaje_error = "Las contraseñas no coinciden.";
    }

    // Validar los requisitos de la contraseña
    if (strlen($password) < 8) {
        $mensaje_error = "La contraseña debe tener al menos 8 caracteres.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $mensaje_error = "La contraseña debe tener al menos una letra mayúscula.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $mensaje_error = "La contraseña debe tener al menos una letra minúscula.";
    }
    if (!preg_match('/\d/', $password)) {
        $mensaje_error = "La contraseña debe tener al menos un número.";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $mensaje_error = "La contraseña debe tener al menos un carácter especial.";
    }

    // Validar que el nombre tenga al menos un nombre y dos apellidos (mínimo 3 palabras)
    if (!preg_match('/^([\p{L}]+\s){2,}[\p{L}]+$/u', $nombre)) {
    $mensaje_error = "El campo Nombres y Apellidos debe contener al menos un nombre y dos apellidos.";
    }

    // Verificar si el correo, documento o teléfono ya están registrados
    $sql = "SELECT * FROM usuarios WHERE Correo = ? OR Documento = ? OR Telefono = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $correo, $documento, $telefono);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Al menos uno de los datos ya está registrado
        $mensaje_error = "Ya existe un usuario con el mismo correo, documento o teléfono.";
    }

    // Si no hubo errores, proceder con el registro
    if (empty($mensaje_error)) {
        // Encriptar la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Generar un código de verificación aleatorio
        $codigo_verificacion = rand(100000, 999999);

        // Insertar datos en la base de datos (sin confirmar todavía)
        $sql = "INSERT INTO usuarios (Nombre, Documento, Telefono, Direccion, Correo, Rol, Contrasena, Verificado) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)"; // 'Verificado' es 0 al principio (pendiente)

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissss", $nombre, $documento, $telefono, $direccion, $correo, $rol, $hashed_password);

        if ($stmt->execute()) {
            // Guardar el código de verificación y el correo en la sesión
            $_SESSION['codigo_verificacion'] = $codigo_verificacion;
            $_SESSION['correo'] = $correo; // Guardar el correo en la sesión para usarlo después

            // Enviar correo de confirmación
            $mail = new PHPMailer(true);
            try {
                // Configura el servidor SMTP
                $mail->isSMTP();
                $mail->Host = ''; // Usar el servidor SMTP de Hostinguer
                $mail->SMTPAuth = true;
                $mail->Username = ''; // Tu correo
                $mail->Password = ''; // Contraseña
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465; //puerto

                // Destinatarios
                $mail->setFrom('', '');
                $mail->addAddress($correo); // Agregar destinatario

                // Contenido del correo
                $mail->isHTML(true);
                $mail->Subject = 'Bienvenido a Market-Sena';
                $mail->Body = "Tu código de verificación es: <strong>$codigo_verificacion</strong>";

                // Enviar correo
                $mail->send();
                echo 'El mensaje ha sido enviado. Revisa tu correo para el código de verificación.';
            } catch (Exception $e) {
                echo "El mensaje no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}";
            }

            // Redirigir a la página de verificación después de un registro exitoso
            header("Location: verificar.php"); // Cambia a la página de verificación
            exit(); // Detener la ejecución
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Market Sena</title>
    <link rel="icon" href="logo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            /* Cambiar a una fuente más atractiva */
            background-color: #001f3f;
            /* Azul oscuro */
            color: white;
            /* Texto blanco para el título */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-container {
            background-color: rgba(255, 255, 255, 0.8);
            /* Fondo blanco semi-transparente */
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            max-width: 630px;
            /* Ancho más grande */
            backdrop-filter: blur(30px);
            /* Filtro de fondo transparente con efecto difuminado */
            -webkit-backdrop-filter: blur(30px);
            /* Compatibilidad con navegadores basados en WebKit */
        }

        .requirement {
            color: red;
            /* Color inicial de los requisitos */
            font-size: 0.8rem;
            /* Tamaño de fuente más pequeño */
        }

        .fulfilled {
            color: green;
            /* Color cuando el requisito se cumple */
        }

        .form-title {
            display: flex;
            justify-content: center;
            /* Centra el título */
            color: white;
            margin-top: 5%;
            margin-bottom: 5%;
            font-size: 2.5rem;
            /* Ajuste de tamaño de fuente */
        }

        @media (max-width: 576px) {
            .form-title {
                font-size: 1,8rem;
                /* Reducir el tamaño en pantallas pequeñas */
                margin-left:15%;
            }
        }

        label {
            color: black;
            /* Color negro para los labels */
        }
    </style>
</head>

<body>
    <div>
        <div>
            <h1 class="form-title">Registrate en Market-Sena
            </h1>
            <div class="form-container">
                <?php if ($mensaje_error): ?>
                    <div class="alert alert-danger error-message">
                        <?php echo $mensaje_error; ?>
                    </div>
                <?php endif; ?>
                <form method="post" class="row g-3">
            <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombres y Apellidos:</label>
                    <input type="text" name="nombre" class="form-control" required
                    pattern="([A-Za-zÁáÉéÍíÓóÚúÑñ]+\s){2,}[A-Za-zÁáÉéÍíÓóÚúÑñ]+" title="Por favor, ingresa al menos dos nombres y un apellido (con letras y tildes)" placeholder="Ingrese su Nombre Completo">
                    </div>
                    <div class="col-md-6">
                        <label for="documento" class="form-label">Documento de Identidad:</label>
                        <input type="number" name="documento" class="form-control" required placeholder="Ingrese su Documento">
                    </div>
                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Teléfono:</label>
                        <input type="number" name="telefono" class="form-control" required placeholder="Ingrese su Teléfono">
                    </div>
                    <div class="col-md-6">
                        <label for="direccion" class="form-label">Dirección de Residencia:</label>
                        <input type="text" name="direccion" class="form-control" required placeholder="Ingrese su Dirección">
                    </div>
                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo Electrónico:</label>
                        <input type="email" name="correo" class="form-control" required placeholder="Ingrese su Correo">
                    </div>
                    <div class="col-md-6">
                        <label for="rol" class="form-label">Seleccione su Rol:</label>
                        <select name="rol" class="form-select" required>
                            <option value="vendedor">Vendedor</option>
                            <option value="cliente">Cliente</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Contraseña:</label>
                        <input type="password" name="password" id="password" class="form-control" required placeholder="Ingrese su Contraseña">
                        <div id="password-requirements" class="requirement">
                            <ul>
                                <li id="length" class="requirement">Al menos 8 caracteres</li>
                                <li id="uppercase" class="requirement">Al menos una letra mayúscula</li>
                                <li id="lowercase" class="requirement">Al menos una letra minúscula</li>
                                <li id="number" class="requirement">Al menos un número</li>
                                <li id="special" class="requirement">Al menos un carácter especial</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirmar Contraseña:</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Confirme su Contraseña">
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary btn-login">Registrarse</button>
                    </div>
                    <div class="col-md-6">
                        <p style="color: black;">¿Ya tienes una cuenta? <br> <a href="login.php" style="color: #007bff;">Inicia Sesión aquí</a></p>
                    </div>
                </form>
            </div>
        </div>
        <script>
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const lengthRequirement = document.getElementById('length');
            const uppercaseRequirement = document.getElementById('uppercase');
            const lowercaseRequirement = document.getElementById('lowercase');
            const numberRequirement = document.getElementById('number');
            const specialRequirement = document.getElementById('special');
            const passwordError = document.getElementById('password-error');
            const confirmPasswordError = document.getElementById('confirm-password-error');

            // Validación en tiempo real de la contraseña
            passwordInput.addEventListener('input', function() {
                const passwordValue = passwordInput.value;
                lengthRequirement.classList.toggle('fulfilled', passwordValue.length >= 8);
                uppercaseRequirement.classList.toggle('fulfilled', /[A-Z]/.test(passwordValue));
                lowercaseRequirement.classList.toggle('fulfilled', /[a-z]/.test(passwordValue));
                numberRequirement.classList.toggle('fulfilled', /\d/.test(passwordValue));
                specialRequirement.classList.toggle('fulfilled', /[!@#$%^&*(),.?":{}|<>]/.test(passwordValue));

                if (passwordValue.length < 8) {
                    passwordError.textContent = "La contraseña debe tener al menos 8 caracteres.";
                } else if (!/[A-Z]/.test(passwordValue)) {
                    passwordError.textContent = "La contraseña debe tener al menos una letra mayúscula.";
                } else if (!/[a-z]/.test(passwordValue)) {
                    passwordError.textContent = "La contraseña debe tener al menos una letra minúscula.";
                } else if (!/\d/.test(passwordValue)) {
                    passwordError.textContent = "La contraseña debe tener al menos un número.";
                } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(passwordValue)) {
                    passwordError.textContent = "La contraseña debe tener al menos un carácter especial.";
                } else {
                    passwordError.textContent = ""; // No hay errores
                }
            });

            confirmPasswordInput.addEventListener('input', function() {
                const passwordsMatch = passwordInput.value === confirmPasswordInput.value;
                confirmPasswordInput.setCustomValidity(passwordsMatch ? '' : 'Las contraseñas no coinciden');
                if (!passwordsMatch) {
                    confirmPasswordError.textContent = "Las contraseñas no coinciden.";
                } else {
                    confirmPasswordError.textContent = ""; // Las contraseñas coinciden
                }
            });
        </script>
</body>

</html>
