<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UsuarioRepository;
use App\Services\AuthService;
use App\Support\ApiException;
use App\Support\Config;
use App\Support\Json;
use App\Support\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    public function __construct(
        private AuthService $auth,
        private JwtService $jwt,
        private UsuarioRepository $usuarios,
    ) {}

    public function login(Request $req, Response $res): Response
    {
        $body = (array) $req->getParsedBody();
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new ApiException(422, 'Completá el email y la contraseña.');
        }

        $ip = $req->getServerParams()['REMOTE_ADDR'] ?? 'desconocida';
        $usuario = $this->auth->login($email, $password, $ip);

        $res = $this->conCookie($res, $this->jwt->emitir($usuario), $this->jwt->ttlSegundos());
        return Json::write($res, ['user' => $usuario]);
    }

    public function me(Request $req, Response $res): Response
    {
        $id = $req->getAttribute('usuario')['id'];
        return Json::write($res, ['user' => $this->usuarios->buscarPorId($id)]);
    }

    public function logout(Request $req, Response $res): Response
    {
        return Json::write($this->conCookie($res, '', 0), ['ok' => true]);
    }

    private function conCookie(Response $res, string $valor, int $maxAge): Response
    {
        // En producción (HTTPS) la cookie además es Secure.
        $secure = Config::get('APP_ENV', 'prod') === 'local' ? '' : '; Secure';

        return $res->withHeader('Set-Cookie', sprintf(
            'token=%s; Path=/; Max-Age=%d; HttpOnly; SameSite=Strict%s',
            $valor,
            $maxAge,
            $secure
        ));
    }
}