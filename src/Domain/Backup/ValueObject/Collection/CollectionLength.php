<?php declare(strict_types=1);

namespace App\Domain\Backup\ValueObject\Collection;

use App\Domain\Backup\Exception\ValueObjectException;
use App\Domain\Common\ValueObject\Numeric\PositiveNumberOrZero;

class CollectionLength extends PositiveNumberOrZero
{
    protected static function getExceptionType(): string
    {
        return ValueObjectException::class;
    }
}
