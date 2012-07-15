<?php

require("./StatsD.php");

/**
 * Designed to work with PHPUnit
 */
class StatsDTest extends PHPUnit_Framework_TestCase {
   public function testIncrement() {
      StatsDMocker::increment("test-inc");
      $this->assertSame("test-inc:1|c", StatsDMocker::getWrittenData());

      StatsDMocker::increment("test-inc", 1);
      $this->assertSame("test-inc:1|c", StatsDMocker::getWrittenData());
   }
}

class StatsDMocker extends StatsD {
   protected static $writtenData;
   public static $writeImmediately = true;

   protected static function sendAsUDP($data) {
      self::$writtenData .= $data;
   }

   public static function getWrittenData() {
      $data = self::$writtenData;
      self::$writtenData = "";
      return $data;
   }
}
