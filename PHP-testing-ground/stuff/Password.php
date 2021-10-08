<?php

/**
 * Password class to obfuscate secret string from accidental printing via serialization, typecasting, var_dump, etc. 
 */
final class Password {
  const SURROGATE = '******';

  public function __construct() {
    return $this;
  }

  public function __debugInfo() {
    return ['password' => self::SURROGATE];
  }

  public static function __set_state($_) {
    return null;
  }

  public function __toString() {
    return self::SURROGATE;
  }
  
  /**
   * Set password.
   * 
   * @param scalar $password
   * @return $this
   */
  public function set($password) {
    if ($password !== null) { $this->vault((string) $password); }
    return $this;
  }

  /**
   * Get password.
   * 
   * @return string
   */
  public function get() {
    return (string) $this->vault();
  }
  
  /**
   * The main password storage unit. The password value is stored in the function's inner
   * variable, wich is declared static, therefore it cannot be serialized or accessed from outside
   * of the function.
   * 
   * If a scalar, non-NULL value was passed, it stores the argument.
   * Returns the password if no argument was passed or returns NULL otherwise.
   * 
   * @param null|string $password
   * @return mixed
   */
  protected function vault($password = null) {
    static $pwd = null;    
    if ($password === null) { return $pwd; }
    if (is_scalar($password)) { $pwd = $password; }
  }
}

// -------------------------------------------------

try {
    $p = (new Password())->set("SECRET");
} catch (Exception $e) {
    var_dump($e);
}

$col = function ($s, $c = 31) { return "\e[0;1;{$c}m{$s}\e[0m"; }; // console colorize
echo $col("var_dump(\$PasswosrdObj) = "); var_dump( $p );
echo $col("var_export(\$PasswordObj) = ").      var_export( var_export($p, true), true) ."\n";
echo $col("(string) \$PasswordObj = ").         var_export( (string) $p, true) . "\n";
echo $col("\"\$PasswordObj\" = ").              var_export( "$p", true) ."\n";
echo $col("print_r(\$PasswordObj) = ").         var_export( print_r($p, true), true) ."\n";
echo $col("get_object_vars(\$PasswordObj) = "). var_export( get_object_vars($p), true) ."\n";
echo $col("get_class_vars(\$PasswordObj) = ").  var_export( get_class_vars($p), true) ."\n";
echo $col("isset(\$PasswordObj->pwd) = ").      var_export( isset($p->pwd), true) ."\n";
echo $col("serialize(\$PasswordObj) = ").       var_export( serialize($p), true) ."\n";

// The only way to access the password is the built-in getter method:
echo "-----\n". $col("\$PasswordObj->get() = "). $col(var_export( $p->get(), true), 33) ."\n";

