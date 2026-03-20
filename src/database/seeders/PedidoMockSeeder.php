<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pedido;
use App\Models\Loja;

class PedidoMockSeeder extends Seeder
{
    public function run(): void
    {
        $loja = Loja::first();

        $pedidos = [
            // Pedido 1 — dentro da Rota 1 apenas
            [
                'numero_pedido' => '100000001',
                'status'        => 'separacao',
                'payload'       => [
                    'displayId' => '100000001',
                    'status'    => 'CREATED',
                    'type'      => 'DELIVERY',
                    'createdAt' => now()->toIso8601String(),
                    'merchant'  => ['id' => $loja->ze_merchant_id ?? 12345],
                    'items'     => [
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
                    'extraInfo' => '',
                ],
            ],

            // Pedido 2 — sobreposto Rota 1 e Rota 2
            [
                'numero_pedido' => '100000002',
                'status'        => 'separacao',
                'payload'       => [
                    'displayId' => '100000002',
                    'status'    => 'CREATED',
                    'type'      => 'DELIVERY',
                    'createdAt' => now()->toIso8601String(),
                    'merchant'  => ['id' => $loja->ze_merchant_id ?? 12345],
                    'items'     => [
                        [
                            'id'         => 2,
                            'name'       => 'Skol Lata 350ml',
                            'quantity'   => 6,
                            'unitPrice'  => ['value' => '4.50', 'currency' => 'BRL'],
                            'totalPrice' => ['value' => '27.00', 'currency' => 'BRL'],
                        ]
                    ],
                    'total' => [
                        'orderAmount' => ['value' => '27.00', 'currency' => 'BRL']
                    ],
                    'customer' => [
                        'name'  => 'Maria Oliveira',
                        'phone' => ['number' => '5521999990002'],
                    ],
                    'delivery' => [
                        'deliveredBy' => 'MERCHANT',
                        'pickupCode'  => 'DEF456',
                        'deliveryAddress' => [
                            'streetName'       => 'Avenida Central',
                            'streetNumber'     => '250',
                            'neighborhood'     => 'Icaraí',
                            'city'             => 'Niterói',
                            'state'            => 'RJ',
                            'formattedAddress' => 'Avenida Central, 250 - Icaraí - Niterói/RJ',
                            'coordinates'      => [
                                'latitude'  => -22.950,
                                'longitude' => -43.032,
                            ],
                        ],
                    ],
                    'extraInfo' => 'Entregar na portaria',
                ],
            ],

            // Pedido 3 — sobreposto Rota 2 e Rota 3
            [
                'numero_pedido' => '100000003',
                'status'        => 'separacao',
                'payload'       => [
                    'displayId' => '100000003',
                    'status'    => 'CREATED',
                    'type'      => 'DELIVERY',
                    'createdAt' => now()->toIso8601String(),
                    'merchant'  => ['id' => $loja->ze_merchant_id ?? 12345],
                    'items'     => [
                        [
                            'id'         => 3,
                            'name'       => 'Budweiser Long Neck',
                            'quantity'   => 4,
                            'unitPrice'  => ['value' => '8.00', 'currency' => 'BRL'],
                            'totalPrice' => ['value' => '32.00', 'currency' => 'BRL'],
                        ]
                    ],
                    'total' => [
                        'orderAmount' => ['value' => '32.00', 'currency' => 'BRL']
                    ],
                    'customer' => [
                        'name'  => 'Carlos Santos',
                        'phone' => ['number' => '5521999990003'],
                    ],
                    'delivery' => [
                        'deliveredBy' => 'MERCHANT',
                        'pickupCode'  => 'GHI789',
                        'deliveryAddress' => [
                            'streetName'       => 'Rua do Comércio',
                            'streetNumber'     => '45',
                            'neighborhood'     => 'São Francisco',
                            'city'             => 'Niterói',
                            'state'            => 'RJ',
                            'formattedAddress' => 'Rua do Comércio, 45 - São Francisco - Niterói/RJ',
                            'coordinates'      => [
                                'latitude'  => -22.938,
                                'longitude' => -43.028,
                            ],
                        ],
                    ],
                    'extraInfo' => '',
                ],
            ],

            // Pedido 4 — dentro da Rota 3 apenas
            [
                'numero_pedido' => '100000004',
                'status'        => 'separacao',
                'payload'       => [
                    'displayId' => '100000004',
                    'status'    => 'CREATED',
                    'type'      => 'DELIVERY',
                    'createdAt' => now()->toIso8601String(),
                    'merchant'  => ['id' => $loja->ze_merchant_id ?? 12345],
                    'items'     => [
                        [
                            'id'         => 4,
                            'name'       => 'Água Mineral 1.5L',
                            'quantity'   => 3,
                            'unitPrice'  => ['value' => '3.00', 'currency' => 'BRL'],
                            'totalPrice' => ['value' => '9.00', 'currency' => 'BRL'],
                        ]
                    ],
                    'total' => [
                        'orderAmount' => ['value' => '9.00', 'currency' => 'BRL']
                    ],
                    'customer' => [
                        'name'  => 'Ana Costa',
                        'phone' => ['number' => '5521999990004'],
                    ],
                    'delivery' => [
                        'deliveredBy' => 'MERCHANT',
                        'pickupCode'  => 'JKL012',
                        'deliveryAddress' => [
                            'streetName'       => 'Estrada das Pedras',
                            'streetNumber'     => '800',
                            'neighborhood'     => 'Pendotiba',
                            'city'             => 'Niterói',
                            'state'            => 'RJ',
                            'formattedAddress' => 'Estrada das Pedras, 800 - Pendotiba - Niterói/RJ',
                            'coordinates'      => [
                                'latitude'  => -22.918,
                                'longitude' => -42.990,
                            ],
                        ],
                    ],
                    'extraInfo' => 'Apartamento 302',
                ],
            ],

            // Pedido 5 — fora de todas as rotas
            [
                'numero_pedido' => '100000005',
                'status'        => 'separacao',
                'payload'       => [
                    'displayId' => '100000005',
                    'status'    => 'CREATED',
                    'type'      => 'DELIVERY',
                    'createdAt' => now()->toIso8601String(),
                    'merchant'  => ['id' => $loja->ze_merchant_id ?? 12345],
                    'items'     => [
                        [
                            'id'         => 5,
                            'name'       => 'Refrigerante Cola 2L',
                            'quantity'   => 1,
                            'unitPrice'  => ['value' => '10.00', 'currency' => 'BRL'],
                            'totalPrice' => ['value' => '10.00', 'currency' => 'BRL'],
                        ]
                    ],
                    'total' => [
                        'orderAmount' => ['value' => '10.00', 'currency' => 'BRL']
                    ],
                    'customer' => [
                        'name'  => 'Pedro Lima',
                        'phone' => ['number' => '5521999990005'],
                    ],
                    'delivery' => [
                        'deliveredBy' => 'MERCHANT',
                        'pickupCode'  => 'MNO345',
                        'deliveryAddress' => [
                            'streetName'       => 'Rua Distante',
                            'streetNumber'     => '1',
                            'neighborhood'     => 'Fora da Área',
                            'city'             => 'Niterói',
                            'state'            => 'RJ',
                            'formattedAddress' => 'Rua Distante, 1 - Fora da Área - Niterói/RJ',
                            'coordinates'      => [
                                'latitude'  => -22.880,
                                'longitude' => -43.100,
                            ],
                        ],
                    ],
                    'extraInfo' => '',
                ],
            ],
        ];

        foreach ($pedidos as $pedido) {
            Pedido::create([
                'loja_id'       => $loja->id,
                'numero_pedido' => $pedido['numero_pedido'],
                'status'        => $pedido['status'],
                'payload'       => $pedido['payload'],
            ]);
        }

        $this->command->info('5 pedidos mock criados!');
    }
}