<?php

namespace App\Mock;

class PedidoMock
{
    // Polígono máximo de cobertura
    const POLIGONO = [
        ["lat" => -22.95384100367748, "lng" => -43.062973022460945],
        ["lat" => -22.935187562488984, "lng" => -43.078422546386726],
        ["lat" => -22.9212748485146, "lng" => -43.06777954101563],
        ["lat" => -22.919061330457776, "lng" => -43.03653717041016],
        ["lat" => -22.911155613684706, "lng" => -43.0059814453125],
        ["lat" => -22.90609571297535, "lng" => -42.98297882080079],
        ["lat" => -22.926650384675423, "lng" => -42.97542572021485],
        ["lat" => -22.93961403539756, "lng" => -42.97920227050782],
        ["lat" => -22.94815039584654, "lng" => -42.99293518066407],
        ["lat" => -22.959215246845364, "lng" => -43.01525115966797],
        ["lat" => -22.96395704877516, "lng" => -43.02108764648438],
        ["lat" => -22.971859682466523, "lng" => -43.0169677734375],
        ["lat" => -22.97944577614986, "lng" => -43.01387786865235],
        ["lat" => -22.985767195550043, "lng" => -43.03173065185547],
        ["lat" => -22.98639932122061, "lng" => -43.05713653564454],
        ["lat" => -22.980394007916388, "lng" => -43.06846618652344],
        ["lat" => -22.96869868442463, "lng" => -43.07464599609376],
        ["lat" => -22.96237646660617, "lng" => -43.06537628173829],
        ["lat" => -22.954789415045937, "lng" => -43.062973022460945],
    ];

    // 5 pedidos com coordenadas dentro do polígono
    const PEDIDOS = [
        [
            'numero'   => '100000001',
            'cliente'  => 'João Silva',
            'telefone' => '5521999990001',
            'endereco' => 'Rua das Flores, 100 - Centro - Niterói/RJ',
            'lat'      => -22.955,
            'lng'      => -43.020,
            'itens'    => [['nome' => 'Cerveja Heineken 600ml', 'qtd' => 2, 'valor' => '12.00']],
            'total'    => '24.00',
        ],
        [
            'numero'   => '100000002',
            'cliente'  => 'Maria Oliveira',
            'telefone' => '5521999990002',
            'endereco' => 'Avenida Central, 250 - Icaraí - Niterói/RJ',
            'lat'      => -22.950,
            'lng'      => -43.032,
            'itens'    => [['nome' => 'Skol Lata 350ml', 'qtd' => 6, 'valor' => '4.50']],
            'total'    => '27.00',
        ],
        [
            'numero'   => '100000003',
            'cliente'  => 'Carlos Santos',
            'telefone' => '5521999990003',
            'endereco' => 'Rua do Comércio, 45 - São Francisco - Niterói/RJ',
            'lat'      => -22.938,
            'lng'      => -43.028,
            'itens'    => [['nome' => 'Budweiser Long Neck', 'qtd' => 4, 'valor' => '8.00']],
            'total'    => '32.00',
        ],
        [
            'numero'   => '100000004',
            'cliente'  => 'Ana Costa',
            'telefone' => '5521999990004',
            'endereco' => 'Estrada das Pedras, 800 - Pendotiba - Niterói/RJ',
            'lat'      => -22.945,
            'lng'      => -43.010,
            'itens'    => [['nome' => 'Água Mineral 1.5L', 'qtd' => 3, 'valor' => '3.00']],
            'total'    => '9.00',
        ],
        [
            'numero'   => '100000005',
            'cliente'  => 'Pedro Lima',
            'telefone' => '5521999990005',
            'endereco' => 'Rua Vista Alegre, 33 - Jurujuba - Niterói/RJ',
            'lat'      => -22.960,
            'lng'      => -43.045,
            'itens'    => [['nome' => 'Refrigerante Cola 2L', 'qtd' => 1, 'valor' => '10.00']],
            'total'    => '10.00',
        ],
    ];

    public static function gerar(int $lojaId, string $numeroPedido = '100000001'): array
    {
        $pedido = collect(self::PEDIDOS)
            ->firstWhere('numero', $numeroPedido) ?? self::PEDIDOS[0];

        return [
            'displayId'  => $pedido['numero'],
            'status'     => 'CREATED',
            'type'       => 'DELIVERY',
            'createdAt'  => now()->toIso8601String(),
            'merchant'   => ['id' => $lojaId],
            'items'      => collect($pedido['itens'])->map(fn($i) => [
                'id'         => 1,
                'name'       => $i['nome'],
                'quantity'   => $i['qtd'],
                'unitPrice'  => ['value' => $i['valor'], 'currency' => 'BRL'],
                'totalPrice' => ['value' => number_format($i['valor'] * $i['qtd'], 2, '.', ''), 'currency' => 'BRL'],
            ])->toArray(),
            'total' => [
                'orderAmount' => ['value' => $pedido['total'], 'currency' => 'BRL']
            ],
            'customer' => [
                'name'  => $pedido['cliente'],
                'phone' => ['number' => $pedido['telefone']],
            ],
            'delivery' => [
                'deliveredBy' => 'MERCHANT',
                'pickupCode'  => 'ABC' . substr($pedido['numero'], -3),
                'deliveryAddress' => [
                    'formattedAddress' => $pedido['endereco'],
                    'coordinates'      => [
                        'latitude'  => $pedido['lat'],
                        'longitude' => $pedido['lng'],
                    ],
                ],
            ],
            'extraInfo' => '',
        ];
    }

    public static function todos(): array
    {
        return collect(self::PEDIDOS)->pluck('numero')->toArray();
    }
}