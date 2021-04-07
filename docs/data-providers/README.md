# Data providers

Data providers are the core means to access external data. These are classes which use custom methods to access 
data. 

Available data providers:

* [Http](http.md) - generic HTTP data provider
* [Rest](rest.md) - data provider for REST-based APIs
* [GraphQL](graphql.md) - data provider for GraphQL APOIs

## Common methods

A `DataProvider` interface is used which defines the following common methods for data providers.

### getBaseUri

Return base URI to use for all data requests. 

* Returns a string
  
### getUri

Return URI to use for current data request

* Parameters
    * `string|null $endpoint` Optional endpoint to append to base URI
* Returns a string

### getRequestIdentifier

Return a unique identifier safe to use for caching based on the request

* Parameters
    * `$uri` URI for request
    * `array $context` Array of contextual data (e.g. request options)
* Returns a string
  
### isSuppressErrors

Whether errors are suppressed

* Returns a boolean
  
### getDefaultDecoder

Return default decoder to decode responses.

* Returns an object of type `Strata\Data\Decode\DecoderInterface` or null if no default decoder set

### decode

Decode a response

* Parameters
    * `mixed $response` Response to decode (normally an object or array)
    * `DecoderInterface|null $decoder` Optional decoder, if not set uses getDefaultDecoder()
* Returns the decoded data (normally an array or object)
  
### isCacheEnabled

Is the cache enabled?

* Returns a boolean

### getCache

Return the cache

* Returns an object of type `Strata\Data\Cache\DataCache`
     
### addListener

Adds an event listener that listens on the specified event. See [events](../events.md).

* Parameters
    * `string $eventName` Event name
    * `callable $listener` The listener
    * `int $priority` The higher this value, the earlier an event listener will be triggered in the chain (defaults to 0)

### addSubscriber

Adds an event subscriber. See [events](../events.md).

* Parameters
    * `EventSubscriberInterface $subscriber` Event subscriber
  
### dispatchEvent

Dispatches an event to all registered listeners

* Parameters
    * `Event $event` The event to pass to the event handlers/listeners
    * `string $eventName` The name of the event to dispatch
* Returns an object of type `Symfony\Contracts\EventDispatcher\Event`
