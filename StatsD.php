<?php

/**
 * Sends statistics to the stats daemon over UDP
 **/
class StatsD {
   protected static $addStatsToQueue = false;
   protected static $queuedStats = array();

   /**
    * Log timing information
    *
    * @param string $stats The metric to in log timing info for.
    * @param float $time The ellapsed time (ms) to log
    * @param float|1 $sampleRate the rate (0-1) for sampling.
    **/
   public static function timing($stat, $time, $sampleRate=1) {
      static::queueStats(array($stat => "$time|ms"), $sampleRate);
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
               self::$queuedStats[] = "$stat:$value|@$sampleRate";
            }
         }
      } else {
         foreach($data as $stat => $value) {
            self::$queuedStats[] = "$stat:$value";
         }
      }

      if (!static::$addStatsToQueue) {
         static::sendAllStats();
      }
   }

   protected static function sendAllStats() {
      if (empty(static::$queuedStats)) return;

      static::sendAsUDP(implode("\n", static::$queuedStats));

      static::$queuedStats = array();
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
