<?php
require 'config.php';
session_start();

// Verifica que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirige al login si no hay sesión activa
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_publicacion'])) {
    $id_publicacion = $_POST['id_publicacion'];

    try {
        // Paso 1: Obtener las imágenes asociadas con la publicación
        $stmt = $pdo->prepare("SELECT imagen FROM publicaciones WHERE id_publicacion = ?");
        $stmt->execute([$id_publicacion]);
        $publicacion = $stmt->fetch();

        // Si la publicación existe, eliminar las imágenes
        if ($publicacion && !empty($publicacion['imagen'])) {
            // Convertir el string de las imágenes en un array
            $fotos = explode(',', $publicacion['imagen']);
            
            // Eliminar cada foto del servidor
            foreach ($fotos as $foto) {
                $foto_path = 'uploads/' . $foto; // Ruta donde están almacenadas las imágenes
                if (file_exists($foto_path)) {
                    unlink($foto_path); // Eliminar la imagen
                }
            }
        }

        // Paso 2: Eliminar la publicación de la base de datos
        $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id_publicacion = ?");
        $stmt->execute([$id_publicacion]);

        // Paso 3: Mensaje de éxito
        $_SESSION['mensaje'] = "Publicación eliminada con éxito."; // Guardamos mensaje de éxito
    } catch (PDOException $e) {
        // En caso de error, mostrar un mensaje de error
        $_SESSION['error'] = "Error al eliminar la publicación: " . $e->getMessage();
    }

    // Redirigir a la página de publicaciones
    header("Location: publicaciones.php");
    exit;
} else {
    // Si no se encuentra un ID de publicación, redirigir a la página de publicaciones
    $_SESSION['error'] = "No se encontró la publicación a eliminar.";
    header("Location: publicaciones.php");
    exit;
}
?>
