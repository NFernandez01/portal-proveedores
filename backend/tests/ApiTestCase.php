<?php
declare(strict_types=1);

namespace Tests;

use App\Bootstrap;
use App\Support\Database;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

abstract class ApiTestCase extends TestCase
{
    protected static App $app;

    public static function setUpBeforeClass(): void
    {
        static::$app = Bootstrap::crearApp(Database::connect());
    }

    protected function request(
        string $metodo,
        string $uri,
        array $body = [],
        ?string $token = null,
        ?string $ip = null,
    ): ResponseInterface {
        // IP al azar para que el rate limit de un test no afecte a otro
        $ip ??= '10.0.' . random_int(0, 255) . '.' . random_int(1, 254);
        $req = (new ServerRequestFactory())->createServerRequest($metodo, $uri, ['REMOTE_ADDR' => $ip]);

        // createServerRequest no lee la query string (?estado=...): la cargamos a mano
        parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);
        $req = $req->withQueryParams($query);

        if ($body !== []) {
            $req = $req->withParsedBody($body);
        }
        if ($token !== null) {
            $req = $req->withCookieParams(['token' => $token]);
        }

        return static::$app->handle($req);
    }

    /** Hace login y devuelve el token de la cookie. */
    protected function login(string $email): string
    {
        $res = $this->request('POST', '/api/auth/login', ['email' => $email, 'password' => 'Demo1234!']);
        $this->assertSame(200, $res->getStatusCode());

        preg_match('/token=([^;]+)/', $res->getHeaderLine('Set-Cookie'), $m);
        return $m[1];
    }

    protected function json(ResponseInterface $res): array
    {
        return json_decode((string) $res->getBody(), true);
    }
}