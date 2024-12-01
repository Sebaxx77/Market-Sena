<?php
require 'config.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Filtrar y recoger datos del formulario
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $condicion = filter_input(INPUT_POST, 'condicion', FILTER_SANITIZE_STRING);
    $cantidad_disponible = filter_input(INPUT_POST, 'cantidad_disponible', FILTER_VALIDATE_INT);
    $categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING);
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING); // Campo teléfono
    $whatsapp_link = filter_input(INPUT_POST, 'whatsapp_link', FILTER_SANITIZE_URL);
    $id_usuario = $_SESSION['user_id'];

    // Verificar que los campos obligatorios están completos
    if ($nombre && $descripcion && $precio !== false && $condicion && $cantidad_disponible !== false && $categoria && $telefono) {

        // Validar que la descripción tenga al menos 110 caracteres
        if (strlen($descripcion) < 110) {
            $error_msg = "La descripción debe tener al menos 110 caracteres.";
        }

        // Manejar la subida de imágenes
        $fotos = [];
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['fotos']['name'][$key]);
            $file_tmp = $_FILES['fotos']['tmp_name'][$key];
            $file_type = mime_content_type($file_tmp);
            $file_size = $_FILES['fotos']['size'][$key];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

            // Validar que el archivo es una imagen y no tiene errores
            if (in_array($file_type, $allowed_types) && $file_size <= 5000000) { // Limitar tamaño a 5MB
                $upload_dir = 'uploads/';
                $upload_file = $upload_dir . $file_name;

                // Mover la imagen a la carpeta de uploads
                if (move_uploaded_file($file_tmp, $upload_file)) {
                    $fotos[] = $file_name; // Guardar el nombre del archivo para la base de datos
                } else {
                    echo "Error al mover la imagen: $file_name";
                }
            } else {
                echo "El archivo $file_name no es válido o es demasiado grande.";
            }
        }

        // Verificar si al menos 3 fotos fueron subidas
        if (count($fotos) < 3) {
            $error_msg = "Debes subir al menos 3 fotos del producto.";
        }

        // Si no hay errores, guardar el producto
        if (!isset($error_msg)) {
            // Convertir el array de fotos a una cadena separada por comas
            $fotos_string = implode(',', $fotos);

            // Insertar el nuevo producto en la base de datos
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO publicaciones (nombre, descripcion, precio, condicion, cantidad_disponible, categoria, id_usuario, imagen, whatsapp_link, telefono)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nombre, $descripcion, $precio, $condicion, $cantidad_disponible, $categoria, $id_usuario, $fotos_string, $whatsapp_link, $telefono]);

                echo "Producto creado exitosamente.";
                // Redirigir a la página de éxito o mostrar un mensaje
                header('Location: paginainicial.php?success=1');
                exit;
            } catch (PDOException $e) {
                echo "Error al guardar el producto: " . $e->getMessage();
            }
        }
    } else {
        echo "Por favor, complete todos los campos correctamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketSena - Crear Publicación</title>
    <title>Perfil del Vendedor</title>
    <link rel="icon" href="carrito.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos personalizados */
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #001f3f;
            color: white;
            margin: 0;
        }

        .navbar {
            background-color: #00264d;
        }

        .navbar-brand,
        .navbar-nav .nav-link {
            color: #ffffff;
        }

        .navbar-nav .nav-link:hover {
            color: #007bff;
        }

        .btn-primary,
        .btn-secondary {
            margin-right: 10px;
        }

        /* Mensaje estático para el campo fotos */
        #fotos-msg {
            color: #888;
            font-size: 0.8em;
            margin-top: 5px;
        }

        /* Mensaje estático para el campo de descripción */
        #descripcion-msg {
            color: #888;
            font-size: 0.8em;
            margin-top: 5px;
        }

        /* Mensaje estático para el campo de precio */
        #precio-msg {
            color: #888;
            font-size: 0.8em;
            margin-top: 5px;
        }

        /* Estilo para el campo de precio */
        #precio {
            -webkit-appearance: none;
            /* Para navegadores basados en WebKit (Chrome, Safari) */
            -moz-appearance: textfield;
            /* Para Firefox */
            appearance: none;
            /* Para otros navegadores */
        }

        /* Eliminar los botones de incremento y decremento en el campo de tipo number */
        #precio::-webkit-outer-spin-button,
        #precio::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Formato para el campo de whatsapp */
        #whatsapp_link {
            color: #888;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1>Crear Publicación</h1>
        <a href="paginainicial.php" class="btn btn-m btn-primary mt-3 mb-3">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del Producto</label>
                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ejemplo: Camiseta deportiva Nike" required>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción (color, detalles, función)</label>
                <textarea class="form-control" id="descripcion" name="descripcion" placeholder="Incluye detalles como el color, tamaño y cualquier otra característica" required></textarea>
                <div id="descripcion-msg">La descripción debe tener al menos 110 caracteres.</div>
            </div>

            <div class="mb-3">
                <label for="fotos" class="form-label">Fotos del Producto</label>
                <input type="file" class="form-control" id="fotos" name="fotos[]" accept="image/*" multiple required>
                <?php if (isset($error_msg)): ?>
                    <div class="text-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>
                <div id="fotos-msg">Mínimo 3 fotos del producto.</div>
            </div>

            <div class="mb-3">
                <label for="precio" class="form-label">Precio (en Pesos Colombianos)</label>
                <input type="number" class="form-control" id="precio" name="precio" step="0.01" placeholder="Ejemplo: 50000" required oninput="this.value = this.value.replace(/[^\d]/g, '')">
                <div id="precio-msg">Sin puntos, comas o espacios.</div>
            </div>

            <div class="mb-3">
                <label for="condicion" class="form-label">Condición</label>
                <select class="form-select" id="condicion" name="condicion" required>
                    <option value="" disabled selected>Selecciona una condición</option>
                    <option value="nuevo">Nuevo</option>
                    <option value="usado">Usado</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="cantidad_disponible" class="form-label">Cantidad Disponible (Stock)</label>
                <input type="number" class="form-control" id="cantidad_disponible" name="cantidad_disponible" placeholder="Ejemplo: 10" required>
            </div>

            <div class="mb-3">
                <label for="categoria" class="form-label">Categoría</label>
                <select class="form-select" id="categoria" name="categoria" required>
                    <option value="" disabled selected>Selecciona una categoría</option>
                    <option value="comida">Comida</option>
                    <option value="servicio">Servicio</option>
                    <option value="ropa">Ropa</option>
                    <option value="tecnologia">Tecnología</option>
                    <option value="hogar">Hogar</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono de Contacto</label>
                <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Ejemplo: 3001234567" required pattern="^\d{10}$" title="Debe ingresar un número de teléfono válido de 10 dígitos">
            </div>

            <div class="mb-3">
                <label for="whatsapp_link" class="form-label">Link a tu chat de WhatsApp</label>
                <input type="url" class="form-control" id="whatsapp_link" name="whatsapp_link" placeholder="Ejemplo: https://wa.me/+573001234567" value="https://wa.me/+57" required>
            </div>

            <button type="submit" class="btn btn-primary">Crear Publicación</button>
            <a href="paginainicial.php" class="btn btn-secondary">Volver</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
