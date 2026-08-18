<?php

namespace App\Enums\Sales;

enum ConditionOperator: string
{
    case EQUALS                = '=';
    case NOT_EQUALS             = '!=';
    case GREATER_THAN           = '>';
    case GREATER_THAN_OR_EQUAL  = '>=';
    case LESS_THAN              = '<';
    case LESS_THAN_OR_EQUAL     = '<=';
    case IN                     = 'in';
    case NOT_IN                 = 'not_in';

    public function label(): string
    {
        return match ($this) {
            self::EQUALS               => 'Equals',
            self::NOT_EQUALS            => 'Not Equals',
            self::GREATER_THAN          => 'Greater Than',
            self::GREATER_THAN_OR_EQUAL => 'Greater Than or Equal',
            self::LESS_THAN             => 'Less Than',
            self::LESS_THAN_OR_EQUAL    => 'Less Than or Equal',
            self::IN                    => 'In',
            self::NOT_IN                => 'Not In',
        };
    }

    public function isMultiValue(): bool
    {
        return match ($this) {
            self::IN, self::NOT_IN => true,
            default                 => false,
        };
    }
}
