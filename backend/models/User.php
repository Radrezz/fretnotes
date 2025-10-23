<?php
class User {
    public $id;
    public $username;
    public $email;
    public $password_hash;

    public function __construct($id, $username, $email, $password_hash) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password_hash = $password_hash;
    }

    public static function findByEmail($email) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            return new self($user_data['id'], $user_data['username'], $user_data['email'], $user_data['password_hash']);
        }
        return null;
    }
}
?>
