<?php
 require_once 'config/Database.php';
 class User{
    private $conn;
    private $table='user';
    //les attributs de la table user dans la bd
    public $id;
    public $nom;
    public $prenom;
    public $email;
    public $motdepasse;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    //fonction create utliser lors de l'inscription dans le form register
    public function create() {
        $query = "INSERT INTO " . $this->table . " (nom, prenom, email, motdepasse) VALUES (:nom, :prenom, :email, :motdepasse)"; 
        $stmt = $this->conn->prepare($query);

        $mphashee=password_hash($this->motdepasse, PASSWORD_DEFAULT);

        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":prenom", $this->prenom); 
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":motdepasse", $mphashee);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    } 
        //fonction getbyEmail utlisee dans la connexion dans le form login
        public function getByEmail() {
        $query = "SELECT id, nom, prenom, email, motdepasse 
                  FROM " . $this->table . " 
                  WHERE email = :email 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        return $stmt;
        }
        //fonction emailExists pour verifier si un autre utlisateur est connecte avec le meme email utlise dans l'inscription 
        public function emailExists() {
          $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 0,1";
          $stmt = $this->conn->prepare($query);
          $stmt->bindParam(":email", $this->email);
          $stmt->execute();
        
          return $stmt->rowCount() > 0;
    }
    //fonction usernameExists pour verifier que chaque utlisateur a un nom unique
    public function usernameExists() {
    $query = "SELECT id FROM " . $this->table . " WHERE nom = :nom AND prenom = :prenom LIMIT 0,1";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":nom", $this->nom);
    $stmt->bindParam(":prenom", $this->prenom);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}
 }
 
?>