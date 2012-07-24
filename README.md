This php library makes it simple to send stats you care about to a statsd
daemon and has a little more functionality (and tests) than Etsy's default PHP
implementation.

See: https://github.com/etsy/statsd

The biggest difference is the addition of a Queue which can be paused and
flushed.  This allows you to track hundreds of stats in a short time while
still only sending one UDP packet at the end of whatever it is you are doing.

### Continuous Integration ###
Tested by Travis CI: [![Build Status](https://secure.travis-ci.org/iFixit/statsd-php-client.png?branch=master)](http://travis-ci.org/iFixit/statsd-php-client)
