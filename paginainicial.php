<?php
require 'config.php';
session_start();

// Tiempo de inactividad antes de cerrar la sesión (en segundos)
$inactivity_limit = 1800; // 30 minutos

// Verifica si la sesión está activa
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirigir a login si no hay sesión activa
    exit;
}

// Verifica el tiempo de inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset(); // Limpia la sesión
    session_destroy(); // Destruye la sesión
    header('Location: login.php'); // Redirigir a login
    exit;
}

// Actualiza la última actividad
$_SESSION['last_activity'] = time();

// Verifica si el usuario es vendedor
$es_vendedor = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'vendedor');

// Recuperar la foto del usuario
$usuario_id = $_SESSION['user_id']; // Asegúrate de tener el ID del usuario en la sesión
$stmt = $pdo->prepare("SELECT foto_usuario FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();
$foto_usuario = $usuario['foto_usuario'];

// Verifica si se seleccionó una categoría
$categoria_seleccionada = isset($_GET['categoria']) ? $_GET['categoria'] : null;

// Obtener el término de búsqueda si se ha enviado
$query = isset($_GET['query']) ? $_GET['query'] : '';

// Normalizar el término de búsqueda para ignorar tildes y convertir a minúsculas
$query_normalized = '';
if ($query) {
    $query_normalized = '%' . strtr(mb_strtolower($query, 'UTF-8'), 'áéíóúñ', 'aeioun') . '%';
}

// Construir la consulta SQL para buscar en todas las publicaciones
$sql = "SELECT p.*, u.nombre AS vendedor_nombre, u.foto_usuario AS vendedor_foto
        FROM publicaciones p 
        JOIN usuarios u ON p.id_usuario = u.id_usuario";

// Condiciones para la consulta
$where_clauses = [];

// Si se selecciona una categoría, filtramos por categoría
if ($categoria_seleccionada) {
    $where_clauses[] = "p.categoria = :categoria";
}

// Si hay un término de búsqueda, se aplica a nombre, categoría y condición
if ($query) {
    $where_clauses[] = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                        p.nombre, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE :query_nombre 
                        OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                        p.categoria, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE :query_categoria
                        OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                        p.condicion, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE :query_condicion";
}

// Si hay condiciones de búsqueda, agregamos el WHERE
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

try {
    $stmt = $pdo->prepare($sql);

    // Si hay un término de búsqueda, vincula los parámetros de búsqueda
    if ($query) {
        $stmt->bindValue(':query_nombre', $query_normalized);
        $stmt->bindValue(':query_categoria', $query_normalized);
        $stmt->bindValue(':query_condicion', $query_normalized);
    }

    // Si hay categoría seleccionada, vincula el parámetro de categoría
    if ($categoria_seleccionada) {
        $stmt->bindValue(':categoria', $categoria_seleccionada);
    }

    $stmt->execute();
    // Asigna el resultado a la variable $publicaciones
    $publicaciones = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketSena - Página Inicial</title>
    <link rel="icon" href="logo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #001f3f;
            color: white;
            margin: 0;
            padding-top: 70px;
        }

        .navbar {
            background-color: #00264d;
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: bold;
        }

        .navbar-nav .nav-link {
            color: #ffffff;
        }

        .navbar-nav .nav-link:hover {
            color: #007bff;
        }

        .btn-outline-success {
            border-color: #007bff;
            color: #ffffff;
        }

        .btn-outline-success:hover {
            background-color: #007bff;
            color: white;
        }

        .btn-outline-primary {
            border-color: #0056b3;
            color: #ffffff;
        }

        .btn-outline-primary:hover {
            background-color: #007bff;
            color: white;
        }

        /* Estilo para el texto azul oscuro en el encabezado */
        header {
            background-color: #001f3f;
            text-align: center;
        }

        header h1,
        header p {
            color: #001f3f;
            /* Azul oscuro */
        }

        .dropdown-item:hover {
            background-color: #007bff;
        }

        .cerrar:hover {
            background-color: red;
        }

        .card {
            background-color: #f8f9fa;
            border: none;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            color: #001f3f;
            font-weight: bold;
        }

        .card-text {
            color: #333333;
        }

        /* Fondo azul claro con opacidad para resaltar las letras */
        .card-body {
            background-color: rgba(173, 216, 230, 0.8);
            color: #003366;
            padding: 15px;
        }

        /* Modal */
        .modal-header {
            background-color: #003366;
            color: white;
        }

        .modal-body {
            background-color: #f0f8ff;
            color: #003366;
        }

        .modal-footer {
            background-color: #f0f8ff;
        }

        .modal-title {
            font-size: 1.5rem;
        }

        .modal-content {
            border-radius: 8px;
        }

        .modal-body p {
            margin-bottom: 15px;
        }

        .modal-footer .btn {
            border-radius: 5px;
        }

        /* Estilo para el carrusel */
        .carousel-item img {
            width: 100%;
            height: auto;
            object-fit: contain;
            /* Asegura que la imagen no se distorsione */
            max-height: 350px;
            /* Limitar la altura máxima */
            border: 2px solid #001f3f;
            /* Borde alrededor de las imágenes */
            border-radius: 8px;
            /* Bordes redondeados */
        }

        /* Estilo para las imágenes de la tarjeta */
        .card-img-top {
            width: 100%;
            height: 300px;
            object-fit: contain;
            /* Ajusta la imagen sin recortarla */
            border-bottom: 2px solid #001f3f;
        }


        /* Flechas de navegación del carrusel */
        .carousel-control-prev,
        .carousel-control-next {
            position: absolute;
            top: 50%;
            z-index: 10;
            color: #007bff;
            background-color: rgba(0, 0, 0, 0.5);
            border: none;
            font-size: 2rem;
            padding: 15px;
            transform: translateY(-50%);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="paginainicial.php"><strong>Market - Sena</strong></a>
            <form class="d-flex flex-grow-1 ms-3 me-3" action="" method="GET">
                <input class="form-control me-2" type="search" placeholder="Buscar productos..." aria-label="Search" name="query" style="flex-grow: 1;">
                <button class="btn btn-outline-success" type="submit">Buscar</button>
            </form>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse pb-2" id="navbarNav">
                <?php if ($es_vendedor): ?>
                    <a class="btn btn-outline-primary mt-2" href="crear.php" title="Crear Publicación">
                        <i class="fas fa-plus"></i> Crear Publicación
                    </a>
                <?php endif; ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="paginainicial.php">Inicio</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categorías
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="categoriasDropdown">
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=comida">Comida</a></li>
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=servicio">Servicio</a></li>
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=ropa">Ropa</a></li>
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=tecnologia">Tecnología</a></li>
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=hogar">Hogar</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="soporte.php">Soporte</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php
                            $nombres = explode(" ", $_SESSION['nombre_usuario']);
                            echo htmlspecialchars(implode(" ", array_slice($nombres, 0, 2)));
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="perfil.php">Perfil</a></li>
                            <?php if ($es_vendedor): ?>
                                <li><a class="dropdown-item" href="publicaciones.php">Mis publicaciones</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item cerrar" href="logout.php">Cerrar sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <header class="bg-light text-center py-5">
        <div class="container">
            <!-- Enlace para redirigir a perfil.php -->
            <a href="perfil.php">
                <div class="mb-2 mt-2">
                    <?php
                    // Recuperar la foto del usuario
                    $usuario_id = $_SESSION['user_id']; // Asegúrate de tener el ID del usuario en la sesión
                    $stmt = $pdo->prepare("SELECT foto_usuario FROM usuarios WHERE id_usuario = ?");
                    $stmt->execute([$usuario_id]);
                    $usuario = $stmt->fetch();
                    $foto_usuario = $usuario['foto_usuario'];

                    // Si hay foto, mostrarla
                    if ($foto_usuario && file_exists($foto_usuario)) {
                        echo '<img src="' . htmlspecialchars($foto_usuario) . '" alt="Foto de perfil" class="rounded-circle" width="180" height="180" style="border: 2px solid black;">';
                    } else {
                        // Si no hay foto, mostrar una imagen predeterminada y el mensaje
                        echo '<img src="uploads/default/default_avatar.jpg" alt="Foto de perfil" class="rounded-circle" width="180" height="180" style="border: 2px solid black;">';
                        echo '<p class="text-center text-black mt-2">Añade tu foto de perfil</p>';
                    }
                    ?>
                </div>
            </a>
            <!-- Nombre de usuario -->
            <h1>¡Hola, <?php echo htmlspecialchars(implode(" ", array_slice(explode(" ", $_SESSION['nombre_usuario']), 0, 2))); ?>!</h1>

            <!-- Mensaje debajo del nombre -->
            <p class="lead">
                <?php
                // Mostrar mensaje según si hay búsqueda
                if ($query) {
                    echo "Resultados de búsqueda para: <strong>" . htmlspecialchars($query) . "</strong>";
                } else {
                    echo "Explora nuestros productos destacados del Sena Cba-Mosquera.";
                }
                ?>
            </p>
        </div>
    </header>
    
    <div class="container py-5">
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="align-items-center">
        <div class="alert alert-success text-center mb-3" role="alert">
            Publicación creada con éxito. Revisa tus publicaciones en la sección de tu nombre y haz clic en "Mis publicaciones".
        </div>
    </div>
    <?php endif; ?>
    
        <h1 class="text-white mb-4">
            Productos Disponibles<?php echo $categoria_seleccionada ? " - " . ucfirst($categoria_seleccionada) : ""; ?>
        </h1>
        <div class="row">
            <?php if (count($publicaciones) > 0): ?>
                <?php foreach ($publicaciones as $publicacion): ?>
                    <div class="col-md-4">
                        <div class="card mb-4 shadow-sm">
                            <?php
                            $imagenes = explode(',', $publicacion['imagen']);
                            ?>
                            <!-- Imagen de vista previa optimizada -->
                            <img src="uploads/<?php echo htmlspecialchars($imagenes[0]); ?>" class="card-img-top preview-image" alt="<?php echo htmlspecialchars($publicacion['nombre']); ?>" data-bs-toggle="modal" data-bs-target="#modal<?php echo $publicacion['id_publicacion']; ?>">

                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($publicacion['nombre']); ?></h5>
                                <p class="card-text">
                                    <?php
                                    $descripcion_corta = mb_strimwidth($publicacion['descripcion'], 0, 100, '...');
                                    echo htmlspecialchars($descripcion_corta);
                                    ?>
                                </p>
                                <p><strong>Precio:</strong> $<?php echo number_format(htmlspecialchars($publicacion['precio']), 2); ?> COP</p>
                                <p class="text-muted"><strong>Condición: </strong> <?php echo htmlspecialchars($publicacion['condicion']); ?></p>
                                <p class="text-muted"><strong>Vendedor: </strong><?php echo htmlspecialchars($publicacion['vendedor_nombre']); ?></p>
                                <p class="text-muted"><strong>Teléfono de contacto: </strong><?php echo htmlspecialchars($publicacion['telefono']); ?></p>

                                <!-- Botón Ver más -->
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal<?php echo $publicacion['id_publicacion']; ?>">
                                    Ver más
                                </button>

                                <!-- Botón de WhatsApp -->
                                <?php if (!empty($publicacion['whatsapp_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($publicacion['whatsapp_link']); ?>" target="_blank" class="btn btn-sm btn-success">
                                        Contactar en WhatsApp
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal para mostrar los detalles completos -->
                    <div class="modal fade" id="modal<?php echo $publicacion['id_publicacion']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo $publicacion['id_publicacion']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalLabel<?php echo $publicacion['id_publicacion']; ?>"><?php echo htmlspecialchars($publicacion['nombre']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Carrusel de imágenes con estilo mejorado -->
                                    <div id="carousel<?php echo $publicacion['id_publicacion']; ?>" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
                                        <div class="carousel-inner">
                                            <?php
                                            $imagenes = explode(',', $publicacion['imagen']);
                                            foreach ($imagenes as $index => $imagen):
                                            ?>
                                                <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                                                    <img src="uploads/<?php echo htmlspecialchars($imagen); ?>" class="d-block w-100 preview-image" alt="Imagen del producto">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <!-- Controles del carrusel -->
                                        <button class="carousel-control-prev" type="button" data-bs-target="#carousel<?php echo $publicacion['id_publicacion']; ?>" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Anterior</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#carousel<?php echo $publicacion['id_publicacion']; ?>" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Siguiente</span>
                                        </button>
                                    </div>

                                    <!-- Descripción y detalles -->
                                    <h3 class="mt-4"><strong>Descripción</strong></h3>
                                    <p><?php echo nl2br(htmlspecialchars($publicacion['descripcion'])); ?></p>
                                    <h5><strong>Precio: </strong>$<?php echo number_format(htmlspecialchars($publicacion['precio']), 2, ',', '.'); ?> COP</h5>
                                    <p><strong>Cantidad Disponible:</strong> <?php echo htmlspecialchars($publicacion['cantidad_disponible']); ?></p>
                                    <p><strong>Condición:</strong> <?php echo htmlspecialchars($publicacion['condicion']); ?></p>
                                    <p><strong>Categoría:</strong> <?php echo htmlspecialchars($publicacion['categoria']); ?></p>
                                    <p><strong>Vendedor: </strong><?php echo htmlspecialchars($publicacion['vendedor_nombre']); ?></p>
                                    <p><strong>Teléfono de contacto:</strong> <?php echo htmlspecialchars($publicacion['telefono']); ?></p>

                                    <!-- Botón para ver el perfil del vendedor -->
                                    <a href="perfil_vendedor.php?id=<?php echo $publicacion['id_usuario']; ?>" class="btn btn-sm btn-primary">
                                        Ver perfil del vendedor
                                    </a>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>

                                    <!-- Botón de WhatsApp en el modal -->
                                    <?php if (!empty($publicacion['whatsapp_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($publicacion['whatsapp_link']); ?>" target="_blank" class="btn btn-success">
                                            Contactar en WhatsApp
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-white">No hay productos disponibles.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Cargar las publicaciones desde PHP (variable JSON generada)
        const publicaciones = <?php echo $json_publicaciones; ?>;

        // Filtrar las publicaciones según la categoría seleccionada
        function filtrarPublicaciones(categoria) {
            const contenedor = document.querySelector(".row");
            contenedor.innerHTML = ''; // Limpiar publicaciones existentes

            // Filtrar publicaciones por categoría
            const publicacionesFiltradas = categoria === 'todas' ?
                publicaciones :
                publicaciones.filter(pub => pub.categoria === categoria);

            // Actualizar encabezado
            document.querySelector("h1.text-white").innerText = `Productos Disponibles: ${categoria.charAt(0).toUpperCase() + categoria.slice(1)}`;

            // Mostrar publicaciones filtradas
            if (publicacionesFiltradas.length > 0) {
                publicacionesFiltradas.forEach(pub => {
                    const card = `
                    <div class="col-md-4">
                        <div class="card mb-4 shadow-sm">
                            <img src="uploads/${pub.imagen.split(',')[0]}" class="card-img-top" alt="${pub.nombre}">
                            <div class="card-body">
                                <h5 class="card-title">${pub.nombre}</h5>
                                <p class="card-text">${pub.descripcion.substring(0, 100)}...</p>
                                <p><strong>Precio:</strong> $${parseFloat(pub.precio).toLocaleString('es-CO')} COP</p>
                                <p><strong>Categoría:</strong> ${pub.categoria}</p>
                                <button type="button" class="btn btn-primary btn-sm" onclick="verDetalle(${pub.id_publicacion})">Ver más</button>
                            </div>
                        </div>
                    </div>
                `;
                    contenedor.innerHTML += card;
                });
            } else {
                contenedor.innerHTML = `<p class="text-center text-white">No hay productos disponibles en esta categoría.</p>`;
            }
        }

        // Mostrar detalles (puedes expandir esta función con modales si necesitas más funcionalidad)
        function verDetalle(id) {
            alert("Ver detalles para publicación ID: " + id);
        }

        // Escuchar el cambio en el menú de categorías
        document.querySelector("#categoriasDropdown").addEventListener("click", function(e) {
            if (e.target && e.target.tagName === "A") {
                const categoria = e.target.getAttribute("data-categoria");
                filtrarPublicaciones(categoria);
            }
        });

        // Mostrar todas las publicaciones al cargar
        document.addEventListener("DOMContentLoaded", () => filtrarPublicaciones('todas'));
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>