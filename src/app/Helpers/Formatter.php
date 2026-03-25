<?php

namespace App\Helpers;

class Formatter
{
    // Salva no banco: 5521999999999
    public static function telefoneBanco(string $telefone): string
    {
        $numero = preg_replace('/\D/', '', $telefone);

        // Adiciona 55 se não tiver DDI
        if (strlen($numero) === 10 || strlen($numero) === 11) {
            $numero = '55' . $numero;
        }

        return $numero;
    }

    // Exibe na tela: (21) 99999-9999
    public static function telefoneExibicao(string $telefone): string
    {
        $numero = preg_replace('/\D/', '', $telefone);

        // Remove DDI 55 se tiver
        if (strlen($numero) === 13 && str_starts_with($numero, '55')) {
            $numero = substr($numero, 2);
        }

        // Formata (21) 99999-9999 ou (21) 9999-9999
        if (strlen($numero) === 11) {
            return '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 5) . '-' . substr($numero, 7);
        }

        if (strlen($numero) === 10) {
            return '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 4) . '-' . substr($numero, 6);
        }

        return $telefone;
    }

    // Email sempre lowercase
    public static function email(string $email): string
    {
        return strtolower(trim($email));
    }

    // Nome capitalizado
    public static function nome(string $nome): string
    {
        return mb_convert_case(trim($nome), MB_CASE_TITLE, 'UTF-8');
    }
}