<?php
declare(strict_types=1);

namespace Strata\Data\Transform\Value;

class DateTimeValue extends BaseValue
{
    private ?string $format = null;
    private ?\DateTimeZone $timezone = null;

    /**
     * DateTimeValue constructor.
     * @param string $propertyPath Property path to read data from
     * @param string|null $format Datetime format to create DateTime object from
     * @see https://www.php.net/manual/en/datetime.createfromformat.php
     */
    public function __construct(string $propertyPath, ?string $format = null, ?\DateTimeZone $timeZone = null)
    {
        $this->propertyPath = $propertyPath;
        if (null !== $format) {
            $this->format = $format;
        }
        if (null !== $timeZone) {
            $this->timezone = $timeZone;
        }
    }

    /**
     * Return property as a DateTime object from source data
     *
     * @param $objectOrArray Data to read property from
     * @return DateTime|null
     */
    public function getValue($objectOrArray)
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $value = $propertyAccessor->getValue($objectOrArray, $this->propertyPath);
        if (null === $value) {
            return null;
        }
        if (null !== $this->format) {
            $datetime = \DateTime::createFromFormat($this->format, $value, $this->timezone);
            if (!$datetime) {
                return null;
            }
        }
        try {
            return new \DateTime($value, $this->timezone);
        } catch (\Exception $e) {
            return null;
        }
    }
}