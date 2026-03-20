<?php

namespace App\Mock;

class PedidoMock
{
    public static function gerar(int $lojaId, string $numeroPedido = null): array
    {
        return [
            'displayId'  => $numeroPedido ?? '100000001',
            'status'     => 'CREATED',
            'type'       => 'DELIVERY',
            'createdAt'  => now()->toIso8601String(),
            'merchant'   => ['id' => $lojaId],
            'items'      => [
                [
                    'id'         => 1,
                    'name'       => 'Cerveja Heineken 600ml',
                    'quantity'   => 2,
                    'unitPrice'  => ['value' => '12.00', 'currency' => 'BRL'],
                    'totalPrice' => ['value' => '24.00', 'currency' => 'BRL'],
                ]
            ],
            'total' => [
                'orderAmount' => ['value' => '24.00', 'currency' => 'BRL']
            ],
            'customer' => [
                'name'  => 'João Silva',
                'phone' => ['number' => '5521999990001'],
            ],
            'delivery' => [
                'deliveredBy' => 'MERCHANT',
                'pickupCode'  => 'ABC123',
                'deliveryAddress' => [
                    'streetName'       => 'Rua das Flores',
                    'streetNumber'     => '100',
                    'neighborhood'     => 'Centro',
                    'city'             => 'Niterói',
                    'state'            => 'RJ',
                    'formattedAddress' => 'Rua das Flores, 100 - Centro - Niterói/RJ',
                    'coordinates'      => [
                        'latitude'  => -22.955,
                        'longitude' => -43.020,
                    ],
                ],
            ],
            'extraInfo' => 'Fala Gleison, teste de fluxo do app de controle de motoboy do Ze... Ta ficando pronto',
        ];
    }
}