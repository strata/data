# Events

We use the [Symfony Event system](https://symfony.com/doc/current/components/event_dispatcher.html) to manage events at 
certain points when fetching data. This is useful to add adhoc functionality, such as logging. 

The following events are available when accessing data. 

| Event name    | Class constant | Description | Available event methods |
| ------------- | ------------- | ------------- | ------------- |
| data.request.start | StartEvent::NAME | Runs at the start of a request | getRequestId(), getUri(), getContext() |
| data.request.success | SuccessEvent::NAME | If a request is successful | getRequestId(), getUri(), getContext(), getException() |
| data.request.failure | FailureEvent::NAME | If a request is considered failed | getRequestId(), getUri(), getContext() |
| data.request.decode | DecodeEvent::NAME | After response has been decoded | getDecodedData(), getRequestId(), getUri(), getContext() |

## How to add a listener for a single event

Event listeners are simple callbacks that run when a specific event is dispatched.   

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

An event subscriber is a class that can listen to multiple events. Since it's in a class it's easier to re-use code.

You can add an [event subscriber](https://symfony.com/doc/current/components/event_dispatcher.html#using-event-subscribers) 
which can listen to multiple events via the `addSubscriber(EventSubscriberInterface $subscriber)` method. 

## Available event subscribers

### LoggerSubscriber

Add logging for the data request process.

```php
use Strata\Data\Http\RestApi;
use Strata\Data\Event\Subscriber\LoggerSubscriber;
use Monolog\Logger;

$api = new RestApi();
$api->addSubscriber(new LoggerSubscriber(new Logger('/path/to/log')));
```

### StopwatchSubscriber

Add timing profiling for the Symfony Stopwatch profiler (only recommended in development).

```php
use Strata\Data\Http\RestApi;
use Strata\Data\Event\Subscriber\StopwatchSubscriber;
use Symfony\Component\Stopwatch\Stopwatch;

$api = new RestApi();
$api->addSubscriber(new StopwatchSubscriber(new Stopwatch());
```




