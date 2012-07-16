This php library makes it simple to send stats you care about to a statsd
daemon and has a little more functionality (and tests) than Etsy's default PHP
implementation.

See: https://github.com/etsy/statsd

The biggest difference is the addition of a Queue which can be paused and
flushed.  This allows you to track hundreds of stats in a short time while
still only sending one UDP packet.
