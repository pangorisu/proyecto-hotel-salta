<?php
class IntroduccionModel {
    private $conn;
    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerIntroduccion($idioma) {
        $sql = "SELECT titulo, subtitulo, cuerpo, imagen_url, imagen_alt 
                FROM Contenidos 
                WHERE pagina = 'introduccion' 
                AND modulo = 'home'
                AND idioma_codigo = :idioma
                AND activo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idioma', $idioma, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>