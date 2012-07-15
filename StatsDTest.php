<?php

require("./StatsD.php");

/**
 * Designed to work with PHPUnit
 */
class StatsDTest extends PHPUnit_Framework_TestCase {
   public function testIncrement() {
      StatsDMocker::increment("test-inc");
      $this->assertSame("test-inc:1|c", StatsDMocker::getWrittenData());

      StatsDMocker::updateStats("test-inc", 9);
      $this->assertSame("test-inc:9|c", StatsDMocker::getWrittenData());
   }

   public function testDecrement() {
      StatsDMocker::decrement("test-dec");
      $this->assertSame("test-dec:-1|c", StatsDMocker::getWrittenData());

      StatsDMocker::updateStats("test-dec", -9);
      $this->assertSame("test-dec:-9|c", StatsDMocker::getWrittenData());
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
