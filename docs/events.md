# Events

We use the [Symfony Event system](https://symfony.com/doc/current/components/event_dispatcher.html) to manage events at 
certain points when fetching data. This is useful to add adhoc functionality, such as logging. 

The following events are available. 

| Event name    | Class constant | Description | Available event methods |
| ------------- | ------------- | ------------- | ------------- |
| strata.data.start | StartEvent::NAME | Runs at the start of a request | getResponse(), getContext() |
| strata.data.success | SuccessEvent::NAME | If a request is successful | getResponse(), getContext(), getException() |
| strata.data.failure | FailureEvent::NAME | If a request is considered failed | getResponse(), getContext() |

## How to add a listener for a single event

Use the `addListener(string $eventName, callable $listener, int $priority = 0)` method to add a [listener](https://symfony.com/doc/current/components/event_dispatcher.html#connecting-listeners) 
to an event. A simple example appears below:

```php
$api->addListener(StartRequest::NAME, function(StartRequest $event) {
    // Get properties from event
    $uri = $event->getUri();
    $context = $event->getContext();
    
    // Do something at start of data request
});
```

## How to add an event subscriber

You can add an [event subscriber](https://symfony.com/doc/current/components/event_dispatcher.html#using-event-subscribers) 
which can listen to multiple events via the `addSubscriber(EventSubscriberInterface $subscriber)` method. 

## Available event subscribers

### LoggerSubscriber

Add logging for the data request process.

```php
use Strata\Data\Http\RestApi;
use Strata\Data\Subscriber\Logger;
use Monolog\Logger;

$api = new RestApi();
$api->addSubscriber(new LoggerSubscriber(new Logger('/path/to/log')));
```

### StopwatchProfiler

Add timing profiling for the Symfony Stopwatch profiler (only recommend in development).

```php
use Strata\Data\Http\RestApi;
use Strata\Data\Subscriber\StopwatchProfiler;
use Symfony\Component\Stopwatch\Stopwatch;

$api = new RestApi();
$api->addSubscriber(new StopwatchProfiler(new Stopwatch());
```




