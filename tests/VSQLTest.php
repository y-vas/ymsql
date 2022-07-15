<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use YMSQL\YMSQL;

// // DISABLE INSPET TO prevent from swhowing the inspect mode
$_ENV['VSQL_INSPECT'] = false;
$_ENV[  'DB_HOST'  ] = 'mysql_ymsql';
$_ENV['DB_USERNAME'] = 'root';
$_ENV['DB_PASSWORD'] = 'root';
$_ENV['DB_DATABASE'] = 'dbtest';

final class YMSQL_TEST extends TestCase {
    public $query = null;

    protected function setUp(){
      $this->query = file_get_contents(getcwd().'/tests/example.ym.sql');


      try {
        $this->vsql = new YMSQL();
      } catch (\Exception $e) {
        $this->fail('Could not connect mysqli!');
      }

      $this->assertNotNull(
          $this->vsql,
          "VSQL is not null"
      );
    }

    public function test1(): void {
      $retun_query = $this->vsql->query( $this->query, [] );

      $fp = fopen(getcwd().'/tests/test1.ym.sql', "w+");
      fwrite($fp, $retun_query );
      fclose($fp);

      $test_query = file_get_contents(getcwd().'/tests/test1.ym.sql');

      $this->assertEquals(
          trim($retun_query),
          trim($test_query)
      );
    }

    public function test2(): void {
      $retun_query = $this->vsql->query( $this->query, [
        'memeber' => 3
      ]);

      $fp = fopen(getcwd().'/tests/test2.ym.sql', "w+");
      fwrite($fp, $retun_query );
      fclose($fp);

      $test_query = file_get_contents(getcwd().'/tests/test2.ym.sql');

      $this->assertEquals(
          trim($retun_query),
          trim($test_query)
      );
    }

    public function testMultilevel(): void {
      $retun_query = $this->vsql->query( $this->query, [
        'memeber' => 3,
        '__append' => 'xname',
        'mixed_status' => 3
      ]);

      $fp = fopen(getcwd().'/tests/test3.ym.sql', "w+");
      fwrite($fp, $retun_query );
      fclose($fp);

      $test_query = file_get_contents(getcwd().'/tests/test3.ym.sql');

      $this->assertEquals(
          trim($retun_query),
          trim($test_query)
      );
    }

    public function test4(): void {
      $retun_query = $this->vsql->query( $this->query, [
        'meta' => [
          'test' => 'dasdfa'
        ],
      ]);

      $fp = fopen(getcwd().'/tests/test4.ym.sql', "w+");
      fwrite($fp, $retun_query );
      fclose($fp);

      $test_query = file_get_contents(getcwd().'/tests/test4.ym.sql');

      $this->assertEquals(
          trim($retun_query),
          trim($test_query)
      );
    }


}
