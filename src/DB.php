<?php

class DBymvas {
  public $vquery=''; // given query
  public $vars; // vars used between each query
  public $fetched = [];
  public $query; // query used

  public $connect = false; # resource: DB connection
  public $error;           # string: Error message
  public $errno;           # integer: error no

  // ------------------------------------------------ <  init > ----------------------------------------------------
  function __construct() {
      foreach (array('DB_HOST', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE') as $value ){
        if (!isset($_ENV[$value])) {
          $this->error( "ENV value \$_ENV[" . $value . "] is not set!" );
        }
      }

      $this->error   = false;
      $this->errno   = false;
      $this->vquery  = '';
      $this->query   = '';

      if (!function_exists('mysqli_connect')) {
          if ( function_exists('mysqli_connect_error') ){
            $this->error = mysqli_connect_error();
          }

          if (function_exists('mysqli_connect_errno')) {
            $this->errorno = mysqli_connect_errno();
          }
          
          $this->error("Function mysqli_connect() does not exists. mysqli extension is not enabled?");
      }
  }

  public function connect() {
    $this->connect = mysqli_connect(
      $_ENV[   'DB_HOST'   ],
      $_ENV[ 'DB_USERNAME' ],
      $_ENV[ 'DB_PASSWORD' ],
      $_ENV[ 'DB_DATABASE' ]
    );

    if (!$this->connect) {
      $this->error('Unable to connect to the database!');
    }

    return $this->connect;
  }

  public function secure( $var ) {
      if (is_object($var) || is_array( $var )) {
        return $var;
      }

      if (function_exists('mysqli_real_escape_string')) {
          return mysqli_real_escape_string( $this->connect, $var );
      } elseif (function_exists('mysqli_escape_string')) {
          return mysql_escape_string($var);
      } else {
          return addslashes( $var );
      }
  }

//------------------------------------------------ <  error > ------------------------------------------------------------
  protected function error( $msg , $code = 0 , $debug = false ) {
    if (
      ($_ENV[ 'APP_ENV' ] ?? ''   ) == 'production' ||
      ($_ENV['APP_DEBUG'] ?? null ) == false
    ) {
      throw new \Exception("YMSQL ERROR : " . $this->query . '<*[_]*>' . json_encode($this->vars) );
    }

    if (strtolower($_ENV['APP_TYPE'] ?? '') == 'api' || $debug == 'api') {
      // prepare headers for outputing to json

      $headers = headers_list();

      $origin  = false;
      $methods = false;
      $heads   = false;
      foreach ($headers as $i => $header) {
        if ( str_contains(strtolower($header),strtolower('Access-Control-Allow-Origin')) ){
          $origin = true;
        }
        if ( str_contains(strtolower($header),strtolower('Access-Control-Allow-Methods')) ){
          $methods = true;
        }
        if ( str_contains(strtolower($header),strtolower('Access-Control-Allow-Headers')) ){
          $heads = true;
        }
      }

      if ($origin) { header('Access-Control-Allow-Origin : *'); }
      if ($methods) { header('Access-Control-Allow-Methods : *'); }
      if ($heads) { header('Access-Control-Allow-Headers : *'); }

      die(json_encode([
        "msg"    => $msg,
        'type'   => 'danger',
        "query"  => $this->query  ,
        "yquery" => $this->vquery ,
      ]));
    }

    if ( $_ENV['APP_DEBUG'] || $debug ) {
      // get the info wrapper for error
      $content = file_get_contents(dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'info.html');

      $values = array(
        "ERROR_MESAGES" => $msg,
        "ORIGINALQUERY" => htmlentities(  $this->query  ),
        "TRANSFRMQUERY" => htmlentities(  $this->vquery ),
      );

      foreach ($values as $key => $value ){
        $content = str_replace("<$key>", $value, $content);
      }

      die( $content );
    }

    throw new \Exception("YMSQL ERROR : " . $this->query . '<*[_]*>' . json_encode($this->vars) );
  }

}
