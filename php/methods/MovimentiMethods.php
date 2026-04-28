<?php 

class MovimentiMethods{
    
    private $db;

    public function __construct(){

        $this->db = @new MySQLi(getenv('DB_HOST'),
                                getenv('DB_USER'),
                                getenv('DB_PASSWORD'),
                                getenv('DB_NAME'));
    }

     public function getConnection() {
        return $this->db;
    }
}



?>