<?php
require_once 'models/ContactoModel.php';

class ContactoController {
    private $model;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new ContactoModel($conn);
    }

    public function index($lang) {
    global $conn;
    $contenido = $this->model->obtenerContacto($lang);

    if (!$contenido) {
        $contenido = [
            'titulo' => 'Contenido no disponible',
            'subtitulo' => '',
            'cuerpo' => 'Información no disponible en este idioma.'
        ];
    }

    $errores = [];
    $exito = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'Correo inválido.';
        }
        if ($telefono === '') {
            $errores['telefono'] = 'El teléfono es obligatorio.';
        }
        if ($mensaje === '') {
            $errores['mensaje'] = 'El mensaje es obligatorio.';
        }

        if (empty($errores)) {
            // Obtener emails destinatarios
            $destinatarios = $this->model->obtenerEmailsDestinatarios();
            if (!empty($destinatarios)) {
                $to = implode(',', $destinatarios);
                $subject = "Mensaje de contacto desde el sitio web";
                $body = "Nombre: $nombre\nEmail: $email\nTeléfono: $telefono\n\nMensaje:\n$mensaje";
                $headers = "From: $email\r\nReply-To: $email\r\n";

                // Intentar enviar el correo y capturar errores
                set_error_handler(function($errno, $errstr) use (&$errores) {
                    if (stripos($errstr, 'Failed to connect to mailserver') !== false) {
                        $errores['general'] = 'Error SMTP: El servidor de correo no está configurado.';
                    } else {
                        $errores['general'] = 'Error al enviar el correo. Intente nuevamente.';
                    }
                    return true; // evita que el error se propague
                });

                $mailEnviado = mail($to, $subject, $body, $headers);

                restore_error_handler();

                if ($mailEnviado) {
                    $exito = true;
                } else {
                    if (empty($errores['general'])) {
                        $errores['general'] = 'Error al enviar el correo. Intente nuevamente.';
                    }
                }
            } else {
                $errores['general'] = 'No hay destinatarios configurados.';
            }
        }
    }

    include 'views/layouts/header.php';
    include 'views/pages/contacto.php';
    include 'views/layouts/footer.php';
}
}
?>