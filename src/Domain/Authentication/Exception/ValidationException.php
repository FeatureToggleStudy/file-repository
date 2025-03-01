<?php declare(strict_types=1);

namespace App\Domain\Authentication\Exception;

class ValidationException extends \Exception
{
    /**
     * @var array
     */
    private $byFields;

    public static function createFromFieldsList(array $fields): ValidationException
    {
        foreach ($fields as $field) {
            if (!\is_array($field)) {
                throw new \InvalidArgumentException('Field should contain a list of messages');
            }

            foreach ($field as $message) {
                if (!\is_string($message)) {
                    throw new \InvalidArgumentException('Field\'s message should be a string');
                }
            }
        }

        $new = new static('Validation error', 400);
        $new->byFields = $fields;

        return $new;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->byFields;
    }
}
