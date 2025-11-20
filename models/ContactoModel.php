<?php
class ContactoModel {
    private $conn;
    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerContacto($idioma) {
        $sql = "SELECT titulo, subtitulo, cuerpo 
                FROM Contenidos 
                WHERE pagina = 'contacto' 
                AND modulo = 'home'
                AND idioma_codigo = :idioma
                AND activo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idioma', $idioma, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function obtenerEmailsDestinatarios() {
    $sql = "SELECT email FROM usuarios WHERE rol IN ('administrador', 'operador') AND estado = 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
}
?>