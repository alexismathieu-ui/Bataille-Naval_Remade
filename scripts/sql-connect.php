<?php

class SqlConnect {
    public PDO $db;

    private string $host = '127.0.0.1';
    private string $port = '3306';
    private string $dbname = 'battle';
    private string $user = 'root';
    private string $password = 'Alexis45170';

    public function __construct()
    {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";

        try {
            $this->db = new PDO($dsn, $this->user, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_PERSISTENT, false);
        } catch (PDOException $e) {
            die('Erreur de connexion à la base : ' . $e->getMessage());
        }
    }

    // Helper optionnel (pas obligatoire)
    public function transformDataInDot(array $data): array
    {
        $dataFormated = [];
        foreach ($data as $key => $value) {
            $dataFormated[':' . $key] = $value;
        }
        return $dataFormated;
    }
}
