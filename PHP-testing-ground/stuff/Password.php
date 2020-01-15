<?php

/**
 * Password class to hide secret string from accidental printing via typecasting, var_dump, etc. 
 */
final class Password {
    const surrogate = '******';
    
    protected $pwd = null;
    
    public function __construct() {
        // throw new Exception();
        return $this;
    }
    
    public function __debugInfo() {
        return ['password' => self::surrogate];
    }
    
    public static function __set_state($arr) {
        //$P = new self();
        //return $P;
        return null;
    }
    
    public function __toString() {
        return self::surrogate;
    }
    
    public function set($password) {
        $this->pwd = $password;
        return $this;
    }
    
    public function get() {
        return $this->pwd;
    }
}

// -------------------------------------------------

try {
    $p = (new Password())->set("SECRET");
} catch (Exception $e) {
    var_dump($e);
}

var_dump(
    $p,
    (string) $p,
    "$p",
    print_r($p, 1),
    var_export($p, 1), // var_export exposes inner values
    get_object_vars($p),
    get_class_vars(get_class($p)),
    serialize($p),     // serialize also exposes inner value
    isset($p->pwd),
    // $p->pwd,        // FATAL ERROR
    // $p::pwd,        // FATAL ERROR
    $p->get()          // should print the password
);
