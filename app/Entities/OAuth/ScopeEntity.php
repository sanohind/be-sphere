<?php

namespace App\Entities\OAuth;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use JsonSerializable;

class ScopeEntity implements ScopeEntityInterface, JsonSerializable
{
    use EntityTrait;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }

    public function jsonSerialize(): mixed
    {
        return $this->getIdentifier();
    }
}
