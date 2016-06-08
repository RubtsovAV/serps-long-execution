<?php

namespace RubtsovAV\Serps\Core\Client;

use RubtsovAV\Serps\Core\Exception\ClientNotFoundException;

class Factory
{
    const CLIENT_NAMESPACE = 'RubtsovAV\\Serps\\Client\\';

    public static function getClientInstanceByName($clientName, array $clientConfig = [])
    {
        $clientClassName = static::CLIENT_NAMESPACE . $clientName;
        if (!class_exists($clientClassName)) {
            throw new ClientNotFoundException(
                "client with the name '$clientName' is not found"
            );
        }
        return new $clientClassName($clientConfig);
    }
}
