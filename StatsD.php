<?php

/**
 * Sends statistics to an instance of the statsd daemon over UDP
 *
 * Make changes here: https://github.com/iFixit/statsd-php-client
 * See: https://github.com/etsy/statsd
 **/
class StatsD {
   protected static $host = 'localhost';
   protected static $port = '8125';

   /**
    * If true, stats are added to a queue until a flush is triggered
    * If false, stats are sent immediately, one UDP packet per call
    */
   protected static $addStatsToQueue = false;
   protected static $queuedStats = array();
   protected static $queuedCounters = array();

   /**
    * Log timing information
    *
    * @param string $stats The metric to in log timing info for.
    * @param float $time The ellapsed time (ms) to log
    * @param float|1 $sampleRate the rate (0-1) for sampling.
    **/
   public static function timing($stat, $time, $sampleRate=1) {
      static::queueStats(array($stat => self::num($time) . "|ms"), $sampleRate);
   }

   /**
    * Report the current value of some gauged value.
    *
    * @param string|array $stat The metric to report ong
    * @param integer $value The value for this gauge
    */
   public static function gauge($stat, $value) {
      static::queueStats(array($stat => self::num($value) . "|g"));
   }

   /**
    * Increments one or more stats counters
    *
    * @param string|array $stats The metric(s) to increment.
    * @param float|1 $sampleRate the rate (0-1) for sampling.
    * @return boolean
    **/
   public static function increment($stats, $sampleRate=1) {
      static::updateStat($stats, 1, $sampleRate);
   }

   /**
    * Decrements one or more stats counters.
    *
    * @param string|array $stats The metric(s) to decrement.
    * @param float|1 $sampleRate the rate (0-1) for sampling.
    * @return boolean
    **/
   public static function decrement($stats, $sampleRate=1) {
      static::updateStat($stats, -1, $sampleRate);
   }

   /**
    * Pause and collect all reported stats until flushStatsOutput() is called.
    */
   public static function pauseStatsOutput() {
      static::$addStatsToQueue = true;
   }

   /**
    * Send all stats generated AFTER a call to pauseStatsOutput()
    * and resume immediate sending again.
    */
   public static function flushStatsOutput() {
      static::$addStatsToQueue = false;
      static::sendAllStats();
   }


   /**
    * Updates a counter by an arbitrary amount.
    *
    * @param string|array $stats The metric(s) to update. Should be either a string or array of metrics.
    * @param int|1 $delta The amount to increment/decrement each metric by.
    * @param float|1 $sampleRate the rate (0-1) for sampling.
    * @return boolean
    **/
   public static function updateStat($stat, $delta=1, $sampleRate=1) {
      $deltaStr = self::num($delta);
      if ($sampleRate < 1) {
         if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
            static::$queuedStats[] = "$stat:$deltaStr|c|@". self::num($sampleRate);
         }
      } else {
         if (!isset(static::$queuedCounters[$stat])) {
            static::$queuedCounters[$stat] = 0;
         }
         static::$queuedCounters[$stat] += $delta;
      }

      if (!static::$addStatsToQueue) {
         static::sendAllStats();
      }
   }

   /**
    * Deprecated, works, but will be removed in the future.
    */
   public static function updateStats($stats, $delta=1, $sampleRate=1) {
      if (!is_array($stats)) {
         return self::updateStat($stats, $delta, $sampleRate);
      }
      foreach($stats as $stat) {
         self::updateStat($stat, $delta, $sampleRate);
      }
   }

   /**
    * Add stats to the queue or send them immediately depending on
    * self::$addStatsToQueue
    */
   protected static function queueStats($data, $sampleRate=1) {
      if ($sampleRate < 1) {
         foreach ($data as $stat => $value) {
            if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
               static::$queuedStats[] = "$stat:$value|@". self::num($sampleRate);
            }
         }
      } else {
         foreach($data as $stat => $value) {
            static::$queuedStats[] = "$stat:$value";
         }
      }

      if (!static::$addStatsToQueue) {
         static::sendAllStats();
      }
   }

   /**
    * Flush the queue and send all the stats we have.
    */
   protected static function sendAllStats() {
      if (empty(static::$queuedStats) && empty(static::$queuedCounters))
         return;

      foreach(static::$queuedCounters as $stat => $value) {
         $line = "$stat:$value|c";
         static::$queuedStats[] = $line;
      }

      static::sendAsUDP(implode("\n", self::$queueStats));

      static::$queuedStats = array();
      static::$queuedCounters = array();
   }

   /**
    * Squirt the metrics over UDP
    */
   protected static function sendAsUDP($data) {
      // Wrap this in a try/catch -
      // failures in any of this should be silently ignored
      try {
         $host = static::$host;
         $port = static::$port;
         $fp = fsockopen("udp://$host", $port, $errno, $errstr);
         if (! $fp) { return; }
         // Non-blocking I/O, please.
         stream_set_blocking($fp, 0);
         fwrite($fp, $data);
         fclose($fp);
      } catch (Exception $e) {
      }
   }

   /**
    * This is the fastest way to ensure locale settings don't affect the 
    * decimal separator. Really, this is the only way (besides temporarily 
    * changing the locale) to really get what we want.
    */
   protected static function num($value) {
      return strtr($value, ',', '.');
   }
}
