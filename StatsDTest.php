<?php

require("./StatsD.php");

/**
 * Designed to work with PHPUnit
 *
 * Make changes here: https://github.com/iFixit/statsd-php-client
 */
class StatsDTest extends PHPUnit_Framework_TestCase {
   public function testIncrement() {
      StatsDMocker::increment("test-inc");
      $this->assertSame("test-inc:1|c", StatsDMocker::getWrittenData());
   }

   public function testDecrement() {
      StatsDMocker::decrement("test-dec");
      $this->assertSame("test-dec:-1|c", StatsDMocker::getWrittenData());
   }

   public function testTiming() {
      StatsDMocker::timing("test-tim", 100);
      $this->assertSame("test-tim:100|ms", StatsDMocker::getWrittenData());
   }

   public function testGauges() {
      StatsDMocker::gauge("test-gag", 345);
      $this->assertSame("test-gag:345|g", StatsDMocker::getWrittenData());
   }

   public function testUpdateStats() {
      StatsDMocker::updateStats("test-dec", -9);
      $this->assertSame("test-dec:-9|c", StatsDMocker::getWrittenData());

      StatsDMocker::updateStats("test-inc", 9);
      $this->assertSame("test-inc:9|c", StatsDMocker::getWrittenData());
   }

   public function testSampleRate() {
      StatsDMocker::increment("test-inc", 0);
      StatsDMocker::decrement("test-dec", 0);
      StatsDMocker::updateStats("test-dec", -9, 0);
      StatsDMocker::updateStats("test-inc", 9, 0);
      $this->assertSame("", StatsDMocker::getWrittenData());
   }

   public function testPauseAndFlushCounts() {
      StatsDMocker::pauseStatsOutput();
      StatsDMocker::increment("test-a");
      StatsDMocker::increment("test-b");
      $this->assertSame("", StatsDMocker::getWrittenData());
      StatsDMocker::flushStatsOutput();
      $this->assertSame("test-a:1|c\ntest-b:1|c",
       StatsDMocker::getWrittenData());
   }

   public function testPauseAndFlushSameName() {
      StatsDMocker::pauseStatsOutput();
      StatsDMocker::increment("test-inc");
      StatsDMocker::updateStats("test-inc", 3);
      $this->assertSame("", StatsDMocker::getWrittenData());
      StatsDMocker::flushStatsOutput();
      $this->assertSame("test-inc:4|c",
       StatsDMocker::getWrittenData());
   }

   public function testPauseAndFlushSameNameTiming() {
      StatsDMocker::pauseStatsOutput();
      StatsDMocker::timing("test-tim", 3);
      StatsDMocker::timing("test-tim", 4);
      $this->assertSame("", StatsDMocker::getWrittenData());
      StatsDMocker::flushStatsOutput();
      $this->assertSame("test-tim:3|ms\ntest-tim:4|ms",
       StatsDMocker::getWrittenData());
   }

   public function testFlushResumesImmediateSend() {
      StatsDMocker::pauseStatsOutput();
      StatsDMocker::flushStatsOutput();
      $this->assertSame("", StatsDMocker::getWrittenData());
      StatsDMocker::increment("test-a");
      $this->assertSame("test-a:1|c",
       StatsDMocker::getWrittenData());
   }
}

class StatsDMocker extends StatsD {
   protected static $writtenData;

   protected static function sendAsUDP($data) {
      self::$writtenData .= $data;
   }

   public static function getWrittenData() {
      $data = self::$writtenData;
      self::$writtenData = "";
      return $data;
   }
}
