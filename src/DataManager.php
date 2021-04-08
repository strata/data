<?php

declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Transform\TransformInterface;
use Strata\Data\Validate\ValidatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\EventDispatcher\Event;

class DataManager
{
    private DataProviderInterface $dataProvider;

    /**
     * Property accessor
     * @see https://symfony.com/doc/current/components/property_access.html
     * @see PropertyAccessorInterface
     * @var PropertyAccessor
     */
    private PropertyAccessor $propertyAccessor;

    /** @var ValidatorInterface[] */
    private array $validators = [];

    private array $errorMessages = [];

    /** @var TransformInterface[] */
    private array $transformers = [];

    /** @var ItemStrategyInterface[] */
    private array $items = [];

    /** @var CollectionStrategyInterface[] */
    private array $collections = [];

    /** @var MapperStrategyInterface[] */
    private array $mappers = [];

    /**
     * DataManager constructor.
     * @param DataProviderInterface $dataProvider Pass data provider to help dispatch events
     */
    public function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Adds an event subscriber
     *
     * @param EventSubscriberInterface $subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->dataProvider->addSubscriber($subscriber);
    }

    /**
     * Dispatches an event to all registered listeners
     *
     * @param Event $event The event to pass to the event handlers/listeners
     * @param string $eventName The name of the event to dispatch
     * @return Event The passed $event MUST be returned
     */
    public function dispatchEvent(Event $event, string $eventName): Event
    {
        return $this->dataProvider->dispatchEvent($event, $eventName);
    }

    /**
     * Add validator to validate data
     *
     * @param ValidatorInterface $validator
     */
    public function addValidator(ValidatorInterface $validator)
    {
        $this->validators[] = $validator;
    }

    /**
     * Validate data
     *
     * @param array $data Data to validate
     * @return bool Whether data is valid, access any errors via getErrorMessages()
     */
    public function validate(array $data): bool
    {
        $valid = true;
        $this->errorMessages = [];

        foreach ($this->validators as $validator) {
            $valid = $validator->validate($data);
            if (!$valid) {
                $this->errorMessages[] = $validator->getErrorMessage();
            }
        }

        return $valid;
    }

    /**
     * Return last error messages from a validate operation
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * Add transformer to alter data
     *
     * @param TransformInterface $transformer
     */
    public function addTransformer(TransformInterface $transformer)
    {
        $this->transformers[] = $transformer;
    }

    /**
     * Transform data
     *
     * @param array $data
     * @return array
     */
    public function transform(array $data): array
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->canTransform($data)) {
                $data = $transformer->transform($data);
            }
        }
        return $data;
    }

    /**
     * Setup an item (to return an item from raw data array)
     *
     * @param string $name
     * @param ItemStrategy $strategy
     */
    public function setupItem(string $name, ItemStrategy $strategy)
    {
        $this->items[$name] = $strategy;
    }

    public function getItem(string $name, array $data): ?array
    {
        if (!isset($this->items[$name])) {
            throw new ItemException(sprintf('Item %s not setup, you must call setupItem() first', $name));
        }
        $strategy = $this->items[$name];

        if (!$this->propertyAccessor->isReadable($data, $strategy->getPropertyPath())) {
            return null;
        }

        $data = $this->propertyAccessor->getValue($data, $strategy->getPropertyPath());
        $data = $strategy->transform($data);
        return $data;
    }

    /**
     * Setup a collection (to return a collection from raw data array)
     *
     * @param string $name
     * @param CollectionStrategy $strategy
     */
    public function setupCollection(string $name, CollectionStrategy $strategy)
    {
        $this->collections[$name] = $strategy;
    }

    public function getCollection(string $name, array $data): ?Collection
    {
        if (!isset($this->items[$name])) {
            throw new ItemException(sprintf('Collection %s not setup, you must call setupCollection() first', $name));
        }
        $strategy = $this->items[$name];

        if (!$this->propertyAccessor->isReadable($data, $strategy->getPropertyPath())) {
            return null;
        }

        $data = $this->propertyAccessor->getValue($data, $strategy->getPropertyPath());
        $data = $strategy->transform($data);

        $paginator = $strategy->getPaginator();
        return new Collection($data, $paginator);
    }

    /**
     * Setup a mapper (to map raw data array to an object)
     *
     * @param string $className
     * @param MapperStrategy $strategy
     */
    public function setupMapper(string $className, MapperStrategy $strategy)
    {
        $this->mappers[$className] = $strategy;
    }

    public function mapToObject(string $className): object
    {
        if (!isset($this->mappers[$className])) {
            throw new MapperException(sprintf('Mapper for class %s not found', $className));
        }

        $mapper = $this->mappers[$className];
        $object = new $className();
        $object = $mapper->map($object);
        return $object;
    }
}
