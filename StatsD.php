<?php

/**
 * Sends statistics to the stats daemon over UDP
 **/
class StatsD {
   protected static $writeImmediately = false;
   protected static $queuedStats = array();

   /**
    * Log timing information
    *
    * @param string $stats The metric to in log timing info for.
    * @param float $time The ellapsed time (us) to log
    * @param float|1 $sampleRate the rate (0-1) for sampling.
    **/
   public static function timing($stat, $time, $sampleRate=1) {
      static::queueStats(array($stat => "$time|us"), $sampleRate);
   }

   /**
    * Increments one or more stats counters
    *
    * @param string|array $stats The metric(s) to increment.
    * @param float|1 $sampleRate the rate (0-1) for sampling.
    * @return boolean
    **/
   public static function increment($stats, $sampleRate=1) {
      static::updateStats($stats, 1, $sampleRate);
   }

   /**
    * Decrements one or more stats counters.
    *
    * @param string|array $stats The metric(s) to decrement.
    * @param float|1 $sampleRate the rate (0-1) for sampling.
    * @return boolean
    **/
   public static function decrement($stats, $sampleRate=1) {
      static::updateStats($stats, -1, $sampleRate);
   }

   /**
    * Updates one or more stats counters by arbitrary amounts.
    *
    * @param string|array $stats The metric(s) to update. Should be either a string or array of metrics.
    * @param int|1 $delta The amount to increment/decrement each metric by.
    * @param float|1 $sampleRate the rate (0-1) for sampling.
    * @return boolean
    **/
   public static function updateStats($stats, $delta=1, $sampleRate=1) {
      if (!is_array($stats)) { $stats = array($stats); }
      $data = array();
      foreach($stats as $stat) {
         $data[$stat] = "$delta|c";
      }

      static::queueStats($data, $sampleRate);
   }

   protected static function queueStats($data, $sampleRate=1) {
      // sampling
      $sampledData = array();

      if ($sampleRate < 1) {
         foreach ($data as $stat => $value) {
            if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
               $sampledData[$stat] = "$value|@$sampleRate";
            }
         }
      } else {
         $sampledData = $data;
      }

      if (empty($sampledData)) { return; }

      static::$queuedStats = array_merge(static::$queuedStats, $sampledData);

      if (static::$writeImmediately) {
         static::sendAllStats();
      }
   }

   protected static function sendAllStats() {
      if (empty(static::$queuedStats)) return;

      $data = array();
      foreach (static::$queuedStats as $stat => $value) {
         $data[] = "$stat:$value";
      }

      static::$queuedStats = array();

      static::sendAsUDP(implode("\n", $data));
   }

   /**
    * Squirt the metrics over UDP
    */
   protected static function sendAsUDP($data) {
      if (empty($sampledData)) { return; }

      // Wrap this in a try/catch - failures in any of this should be silently ignored
      try {
         $host = 'localhost';
         $port = 8125;
         $fp = fsockopen("udp://$host", $port, $errno, $errstr);
         if (! $fp) { return; }
         // Non-blocking I/O, please.
         stream_set_blocking($fp, 0);
         fwrite($fp, $data);
         fclose($fp);
      } catch (Exception $e) {
      }
   }
}
