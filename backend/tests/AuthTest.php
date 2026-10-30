<?php
declare(strict_types=1);

namespace Tests;

final class AuthTest extends ApiTestCase
{
    public function test_login_correcto_devuelve_cookie_httponly(): void
    {
        $res = $this->request('POST', '/api/auth/login', ['email' => 'admin@portal.test', 'password' => 'Demo1234!']);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString('HttpOnly', $res->getHeaderLine('Set-Cookie'));
        $this->assertArrayNotHasKey('password_hash', $this->json($res)['user']);
    }

    public function test_password_incorrecto_da_401(): void
    {
        $res = $this->request('POST', '/api/auth/login', ['email' => 'admin@portal.test', 'password' => 'mal']);
        $this->assertSame(401, $res->getStatusCode());
    }

    public function test_sin_token_da_401(): void
    {
        $this->assertSame(401, $this->request('GET', '/api/me')->getStatusCode());
    }

    public function test_token_adulterado_da_401(): void
    {
        $token = $this->login('proveedor1@portal.test');
        $this->assertSame(401, $this->request('GET', '/api/me', [], $token . 'x')->getStatusCode());
    }

    public function test_rate_limit_bloquea_al_sexto_intento(): void
    {
        $ip = '10.99.' . random_int(0, 255) . '.' . random_int(1, 254);
        for ($i = 0; $i < 5; $i++) {
            $this->request('POST', '/api/auth/login', ['email' => 'admin@portal.test', 'password' => 'mal'], null, $ip);
        }
        $res = $this->request('POST', '/api/auth/login', ['email' => 'admin@portal.test', 'password' => 'mal'], null, $ip);

        $this->assertSame(429, $res->getStatusCode());
    }

    /**
     * Bug corregido: antes, un login correcto de OTRA cuenta desde la misma IP borraba
     * todos los intentos fallidos y permitía seguir probando contraseñas sin límite.
     */
    public function test_login_correcto_de_otra_cuenta_no_resetea_el_rate_limit(): void
    {
        $ip = '10.98.' . random_int(0, 255) . '.' . random_int(1, 254);
        $fallar = fn () => $this->request('POST', '/api/auth/login',
            ['email' => 'admin@portal.test', 'password' => 'mal'], null, $ip);

        for ($i = 0; $i < 4; $i++) {
            $fallar();
        }
        // El atacante entra con su propia cuenta desde la misma IP...
        $propia = $this->request('POST', '/api/auth/login',
            ['email' => 'proveedor1@portal.test', 'password' => 'Demo1234!'], null, $ip);
        $this->assertSame(200, $propia->getStatusCode());

        // ...y sigue intentando contra el admin: el 5º fallo cuenta y el 6º se bloquea
        $this->assertSame(401, $fallar()->getStatusCode());
        $this->assertSame(429, $fallar()->getStatusCode());
    }

    public function test_el_email_no_distingue_mayusculas(): void
    {
        $res = $this->request('POST', '/api/auth/login', ['email' => '  Admin@Portal.TEST ', 'password' => 'Demo1234!']);
        $this->assertSame(200, $res->getStatusCode());
    }
}