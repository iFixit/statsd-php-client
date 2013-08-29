This php library makes it simple to send stats you care about to a statsd
daemon and has a little more functionality (and tests) than Etsy's default PHP
implementation.

See: https://github.com/etsy/statsd

The biggest difference is the addition of a Queue which can be paused and
flushed.  This allows you to track hundreds of stats in a short time while
still only sending one UDP packet at the end of whatever it is you are doing.

## Usage
This is meant to be subclassed and the static `$host` and `$port` variables
overridden.

```php
require('StatsD.php');

StatsD::increment("something");

StatsD::timing("something", $time);

StatsD::gauge("something", $value);

// Arbitrary valued counters (instead of inc / dec)
StatsD::updateStat("something", 42, 0.1); // 0.1 sample rate

// Buffer UDP output packets
StatsD::pauseStatsOutput();
// Bunch of StatsD::increment() or others

// Sends one UDP packet instead of one for each call
StatsD::flushStatsOutput();
```


### Continuous Integration ###
Tested by Travis CI: [![Build Status](https://secure.travis-ci.org/iFixit/statsd-php-client.png?branch=master)](http://travis-ci.org/iFixit/statsd-php-client)
