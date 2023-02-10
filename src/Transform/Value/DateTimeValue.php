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
    public function __construct($propertyPath, ?string $format = null, ?\DateTimeZone $timeZone = null)
    {
        parent::__construct($propertyPath);

        if (null !== $format) {
            $this->format = $format;
        }
        if (null !== $timeZone) {
            $this->timezone = $timeZone;
        }
    }

    /**
     * Return property as a DateTime object
     *
     * @param $objectOrArray Data to read property from
     * @return DateTime|null
     */
    public function getValue($objectOrArray)
    {
        $value = parent::getValue($objectOrArray);

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
