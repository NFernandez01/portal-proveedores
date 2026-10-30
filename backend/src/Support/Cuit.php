<?php
declare(strict_types=1);

namespace App\Support;

final class Cuit
{
    private const PESOS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    public static function esValido(string $cuit): bool
    {
        $cuit = preg_replace('/\D/', '', $cuit);   // acepta "30-71234567-1" o "30712345671"
        if (strlen($cuit) !== 11) {
            return false;
        }

        $suma = 0;
        foreach (self::PESOS as $i => $peso) {
            $suma += (int) $cuit[$i] * $peso;
        }

        $verificador = 11 - ($suma % 11);
        if ($verificador === 11) {
            $verificador = 0;
        }
        if ($verificador === 10) {
            return false;   // AFIP no emite CUIT con verificador 10
        }

        return $verificador === (int) $cuit[10];
    }
}