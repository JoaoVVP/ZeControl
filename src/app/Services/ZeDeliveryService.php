<?php

namespace App\Services;

use App\Models\Loja;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ZeDeliveryService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('ZE_API_URL', 'https://seller-public-api.ze.delivery');
    }

    // Autentica e retorna o token (cache de 55 min)
    public function getToken(Loja $loja): ?string
    {
        $cacheKey = "ze_token_loja_{$loja->id}";

        return Cache::remember($cacheKey, now()->addMinutes(55), function () use ($loja) {
            $response = Http::asForm()->post("{$this->baseUrl}/auth", [
                'grant_type'    => 'client_credentials',
                'scope'         => 'orders/read',
                'client_id'     => $loja->ze_client_id,
                'client_secret' => $loja->ze_client_secret,
            ]);

            if ($response->failed()) return null;

            return $response->json('access_token');
        });
    }

    // Busca eventos não consumidos
    public function buscarEventos(Loja $loja, array $tipos = ['CREATED', 'CANCELLED', 'CONCLUDED']): ?array
    {
        $token = $this->getToken($loja);
        if (!$token) return null;

        $response = Http::withHeaders([
            'Authorization'       => "Bearer {$token}",
            'x-polling-merchants' => $loja->ze_merchant_id,
        ])->get("{$this->baseUrl}/events:polling", [
            'eventType' => $tipos,
        ]);

        if ($response->failed()) return null;

        return $response->json();
    }

    // Confirma pedido
    public function confirmarPedido(Loja $loja, string $numeroPedido): bool
    {
        $token = $this->getToken($loja);
        if (!$token) return false;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/orders/{$numeroPedido}/confirm", [
            'createdAt' => now()->toIso8601String(),
        ]);

        return $response->successful();
    }

    // Busca detalhes do pedido
    public function buscarPedido(Loja $loja, string $numeroPedido): ?array
    {
        $token = $this->getToken($loja);
        if (!$token) return null;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->get("{$this->baseUrl}/orders/{$numeroPedido}");

        if ($response->failed()) return null;

        return $response->json();
    }

    // Acknowledgment — marca eventos como consumidos
    public function acknowledgment(Loja $loja, array $eventos): bool
    {
        $token = $this->getToken($loja);
        if (!$token) return false;

        $payload = collect($eventos)->map(fn($e) => [
            'id'        => $e['eventId'],
            'orderId'   => $e['orderId'],
            'eventType' => $e['eventType'],
        ])->toArray();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/events/acknowledgment", $payload);

        return $response->successful();
    }

    // Order Picked — adiciona pedido no app do motoboy
    public function orderPicked(Loja $loja, string $numeroPedido, string $emailMotoboy): bool
    {
        $token = $this->getToken($loja);
        if (!$token) return false;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/logistics/orderPicked/{$numeroPedido}", [
            'email' => $emailMotoboy,
        ]);

        return $response->successful();
    }

    // Start Route
    public function startRoute(Loja $loja, string $numeroPedido, string $emailMotoboy): bool
    {
        $token = $this->getToken($loja);
        if (!$token) return false;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/logistics/startRoute/{$numeroPedido}", [
            'email' => $emailMotoboy,
        ]);

        return $response->successful();
    }
}