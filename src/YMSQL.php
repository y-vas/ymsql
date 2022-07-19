<?php

namespace YMSQL;
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'DB.php');

class YMSQL extends \DBymvas {

  public $map = null;

  public function __construct(){
    parent::__construct();
    $this->connect();
  }

//------------------------------------------------ <  query > ----------------------------------------------------------
  public function query($str , $vrs , $debug = false ) {
    $this->query  = $str;
    $this->vquery = ''; // init again
    $this->vars   = $vrs;

    $str = $this->interpreter( $str, $vrs );
    $str = $this->modifier( $str, $vrs );

    $this->vquery = $str;

    if ( $debug ){
      $this->error( 'Inspect' , 0 , true );
    }

    return $this->vquery;
  }

  public function run(){
    $mysqli = $this->connect;
    $mysqli->query( $this->vquery );
    return $mysqli;
  }

  private function keyInterpreter($x,$chars){
    $parser = '';
    $key    = '';
    $not_valid_parser_values = [
      '\n','\t',' ','{','}',':','(',')','[',']'
    ];

    for ($i=($x-1); $i >= 0; $i--){
      $c = $chars[ $i ];
      if (trim($c) == '' || in_array($c,$not_valid_parser_values)){
        break;
      }
      $parser .= $c;
    }

    for ($i=($x+1); $i < count($chars); $i++) {
      $c = $chars[$i];
      if (trim($c) == '' || in_array($c,$not_valid_parser_values)) {
        break;
      }
      $key .= $c;
    }

    $key    = trim($key);
    $parser = trim($parser);

    if ($key == '') {
      return null;
    }

    $plc = false;
    if ($parser == '' && $chars[$x-1] == ':') {
      $parser = ':';
      $plc    = true;
    }



    $result = [
      'exi' => isset($this->vars[$key]),
      'plc' => $plc   ,
      'key' => $key    ,
      'pas' => $parser ,
      'raw' => $parser . ':' . $key ,
    ];

    return $result;
  }

  private function queryInterpreter($query,$vals){

    foreach ($vals as $k => $v) {
      // if values does not exists
      // if value is placeholder
      if (!$v['exi'] || $v['plc']){
        $query = str_replace($v['raw'],'',$query);
        continue;
      }

      if ($v['exi']){
        $v['var'] = $this->vars[$v['key']];
        $query = str_replace($v['raw'],$this->parser($v),$query);
      }
    }


    return $query;
  }

  protected function interpreter($str,$vrs){
    $chars = str_split( "   " . $str . "   " );

    $skip      = 0;
    $counter   = 0;
    $found = false;
    $positions = [];
    $substitut = [[
      'query'  => '' ,
      'pcount'=> 0 ,
      'scount'=> 0 ,
      'values' => [] ,
      'vcount' => 0  ,
    ]];

    foreach( $chars as $i => $c ){
      if ($skip != 0) { $skip--; continue; }

      if ($chars[$i] == "\\" && ($chars[$i+1]=='{' || $chars[$i+1]=='}')) {
        $substitut[$counter]['query'] .=  $chars[$i+1];
        $skip = 1;
        continue;
      }

      if ($c == ' ' || $c == '\n') {
        $substitut[$counter]['query'] .=  $c;
        continue;
      }


      ////
      if ($c == '{'){
        $counter += 1;
        $positions[] = $i;
        $substitut[] = [
          'query' => '',
          'vcount'=> 0 ,
          'pcount'=> 0 ,
          'scount'=> 0 ,
          'values'=> [],
        ];

        continue;
      }


      if ($c == '}'){
        /////
        $counter -= 1;
        $meta       = array_pop($substitut);
        $last_vals  = $meta['values'];
        $last_query = $meta[ 'query'];
        $new_query = '';

        $x = array_pop($positions);

        //// if elseif else
        $has_elif = ($chars[$i+1] == "-" && $chars[$i+2] == ">");
        $has_else = ($chars[$i+1] == "~" && $chars[$i+2] == ">");

        $had_elif = ($chars[$x-2] == "-" && $chars[$x-1] == ">");
        $had_else = ($chars[$x-2] == "~" && $chars[$x-1] == ">");

        if ($has_elif || $has_else) { $skip += 2; }

        if ( $meta['vcount'] == 0 && !$had_else ){
          continue;
        }

        if ( $meta['vcount'] != $meta['scount'] && $meta['scount'] != $meta['pcount']){
          continue;
        }

        if ( $had_else && $found ){
          $found = false;
          continue;
        }

        $new_query = $this->queryInterpreter($last_query, $meta['values']);

        $substitut[$counter]['query' ] .= $new_query;
        $substitut[$counter]['vcount'] += $meta['vcount'];
        $substitut[$counter]['scount'] += $meta['scount'];
        $substitut[$counter]['pcount'] += $meta['pcount'];
        $substitut[$counter]['values'] = array_merge(
            $meta['values'], $substitut[$counter]['values']
        );

        $found = $had_else ? false:true;
        continue;
      } elseif ( $c == ':' ){
        $arr = $this->keyInterpreter($i,$chars);


        if ($arr != null) {
          $key = $arr['key'];
          $substitut[$counter]['scount'] += 1;
          $substitut[$counter]['vcount'] += $arr['exi'] ? 1:0;
          $substitut[$counter]['pcount'] += $arr['plc'] ? 1:0;
          $substitut[$counter]['values'][$arr['key']] = $arr;
        }

        ///////
      }

      $substitut[$counter]['query'] .= $c;
    }

    return $this->queryInterpreter(
      $substitut[$counter]['query'] , $substitut[$counter]['values']
    );
  }


//------------------------------------------------ <  parser > ----------------------------------------------------------
// parser = parser value ex: d , email , +i
// var = the variable to parse
  public function parser( $content ){
    $parser = $content['pas'];
    $var    = $content['var'];

    // secure the value before inserting it in the query
    $res = $this->secure( $var );

    //---------------------- cases ----------------------
    switch ( $parser ) {
      case 'i': // i = integer
          if ($var === null){
            $res = null;
          } elseif ( !is_numeric($var) ){
            $res = null;
          } else {
            settype( $var, 'int' );
            $res = $var;
          }
          break;

      case 'r': // parse to positive integer
          if ($var === null){
            $res = null;
          } elseif (!is_numeric($var)) {
            $res = null;
          }else{
            settype($var, 'int');
            $res = abs($var);
          }
          break;


      case 's': // parse the value to string
          $v = strval($res);
          $res = (strlen($v) > 0) ? "'". $v . "'": null;
          break;
      case 'q':
          $v = strval($res);
          $res = (strlen($v) > 0) ? '"'. $v . '"': null;
          break;


      case 'f': // parse to float
          if ($var === null){
            $res = null;
          } elseif (!is_numeric($var)) {
            $res = null;
          }else{
            settype($var, 'float');
            $res = $var;
          }
          break;
      case '+f': // parse to positive float
          if ($var === null){
            $res = null;
          } elseif (!is_numeric($var)) {
            $res = null;
          }else{
            settype($var, 'float');
            $res = abs($var);
          }
          break;


      case 'a': // implode the array
          if (!is_array( $res )){  $res = []; }
          $res = "'".implode( ',', $res )."'";
          break;
      case 'c':
          if (!is_array( $res )){
            $res = null; break;
          }
          $res = implode(',' ,  $res );
          break;


      // transform the valie to json
      case 'j':
          $res = "'".json_encode( $res , JSON_UNESCAPED_UNICODE )."'";
          break;
      case 'w':
          json_decode( $var );
          $res = (json_last_error() == JSON_ERROR_NONE) ? "'". $var."'" : "'{}'";
          break;


      case 'd':  // d = date
          settype( $var , 'string' );
          $res = empty( $var ) ? "'1970-01-01'":"'{$var}'";
          if (!(\DateTime::createFromFormat('Y-m-d', $var) !== false)) {
              $res = null;
          }
          break;
      case 'dt': // dt = datetime
          settype($var, 'string');
          $res = empty($var) ? "'1970-01-01 00:00:00'": "'{$var}'";
          if(!( \DateTime::createFromFormat('Y-m-d H:i:s', $var ) !== false ) ){
              $res = null;
          }
          break;


      case 'e':
          if (!filter_var($res, FILTER_VALIDATE_EMAIL)) {
            $res = null;
          } else {
            $res = "'".$res."'";
          }
          break;

      default:
          $v = strval($res);
          $res = (strlen($v) > 0) ? $v : null;
          break;
    }

    return $res;
  }

//-------------------------------------------- <  modifier > ------------------------------------------------------
// the modifier saves how the data sould be fetched once the data is fetched
  private function modifier( $str , $vrs ) {
      preg_match_all('!\s{1,}(?:as|AS)\s{1,}([^,]*)\s{1,}(?:to|TO)\s{1,}(\w+),*!', $str, $m );

      foreach ($m[0] as $k => $full) {
        $s = $m[1][$k];
        $f = $m[2][$k];
        $this->fetched[ trim( $s )] = [trim($f)];
        $str = str_replace($full," AS {$s} ",$str);
      }

      return $str;
  }

//------------------------------------------------ <  get > ------------------------------------------------------------
  public function get( $list = false , $callback = null ) {
      if ( $list === 'output-query' ) {
        return $this->vquery;
      }

      $mysqli = $this->connect;
      $obj    = new \stdClass();
      $count  = 0;


      /// currenty no cleaner way to make it
      if (mysqli_multi_query( $mysqli, $this->vquery )) {
          if ($list === true) {

              //----------------------------------------------------------------
              do { if($result = mysqli_store_result($mysqli) ) {

                while ($proceso = mysqli_fetch_assoc($result)) {
                    $obj->$count = $this->fetch($result, $proceso,$callback);
                    $count++;
                }

                mysqli_free_result($result);
              }

              if (!mysqli_more_results($mysqli)) { break; }
              } while ( mysqli_next_result($mysqli) && mysqli_more_results() );

          } else {
              $result = mysqli_store_result($mysqli);

              if ( !$result ) {
                $this->error( "Fail on query get :".mysqli_error($mysqli) );
              }

              $proceso = mysqli_fetch_assoc( $result );

              if( $proceso == null ){
                $obj = null;
              } else {
                $obj = $this->fetch( $result, $proceso ,$callback );
              }
          };

      } else {
        $this->error("Fail on query get :" . mysqli_error($mysqli));
      }

      return $obj;
  }


// ------------------------------------------------ <  fetch > ----------------------------------------------------
  protected function fetch( $result, $proceso , $callback = null ) {
      $row = new \stdClass();

      $count = 0;
      foreach ($proceso as $key => $value) {
          $direct = $result->fetch_field_direct($count++);
          $ret = $this->_transform_get($value, $direct->type, $key);
          $key = $ret[1];
          $row->$key = $ret[0];
      }


      if ( $callback != null ) {
        $row = $callback( $row , $this->vars );
      }

      return $row;
  }

// ------------------------------------------------ <  _transform_get > ------------------------------------------------
  protected function _transform_get( $val, $datatype, $key ){

      foreach ($this->fetched as $k => $value) {
          if (trim( $key ) == trim( $k )){
              foreach ( $value as $t => $tr ){
                $val = $this->_transform( $tr, $val );
      }}}

      return array( $val, $key );
    }

// ------------------------------------------------ <  _transform > ----------------------------------------------------
  private function _transform( $transform, $val ) {
      if (isset($_ENV['VSQL_UTF8']) && $_ENV['VSQL_UTF8'] == true) {
          $val = utf8_decode( $val );
      }

      switch ( $transform ) {
        case 'json':
            $non = json_decode( $val , true );
            if ( $non != null ){ return $non; }

            $non = json_decode( utf8_encode( $val ), true );

            if ( $non != null ){
              return $non;
            }

            return json_decode($val, true);
        case 'array':
            $non = json_decode($val,true);
            if ( $non != null ){
              return $non;
            }
            return json_decode($val, true);
        case 'explode':
            return explode( ',' , $val );
        case 'array-std':
            $non = json_decode( $val , true );
            if ( $non == null ){
              $non = json_decode( $val, true );
            }
            foreach ( $non as $key => $value ){
              $non[$key] = (object) $value;
            }
            return $non;
        default:
          break;
      }

      return $val;
  }

}
