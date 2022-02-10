<?php declare(strict_types = 1);

namespace Pyz;

use Lcobucci\JWT\Parser;
use League\OAuth2\Server\Exception\OAuthServerException;

class FixMe3
{
    /**
     * @throws \League\OAuth2\Server\Exception\OAuthServerException !
     *
     * @return void
     */
    public function bar(): void
    {
        throw \League\OAuth2\Server\Exception\OAuthServerException::accessDenied('baz');

        new Parser();
    }
}
