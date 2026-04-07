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

    private function endpoint(string $envKey, array $params = []): string
    {
        $path = env($envKey, '');
        foreach ($params as $key => $value) {
            $path = str_replace("{{$key}}", $value, $path);
        }
        return $this->baseUrl . $path;
    }

    // Autentica e retorna o token (cache de 55 min)
    public function getToken(Loja $loja): ?string
    {
        $cacheKey = "ze_token_loja_{$loja->id}";

        return Cache::remember($cacheKey, now()->addMinutes(55), function () use ($loja) {
            $response = Http::asForm()->post($this->endpoint('ZE_AUTH'), [
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
    public function buscarEventos(Loja $loja, array $tipos = ['CREATED', 'CANCELLED', 'CONCLUDED', 'DISPATCHED']): ?array
    {
        $token = $this->getToken($loja);
        if (!$token) return null;

        $response = Http::withHeaders([
            'Authorization'       => "Bearer {$token}",
            'x-polling-merchants' => $loja->ze_merchant_id,
        ])->get($this->endpoint('ZE_EVENTS_POLLING'), [
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
        ])->post($this->endpoint('ZE_ORDER_CONFIRM', ['orderNumber' => $numeroPedido]), [
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
        ])->get($this->endpoint('ZE_ORDER_DETAILS', ['orderNumber' => $numeroPedido]));

        if ($response->failed()) return null;

        return $response->json();
    }

    // Cancela pedido
    public function cancelarPedido(Loja $loja, string $numeroPedido, string $motivo = 'DELIVERY_PROBLEM'): bool
    {
        $token = $this->getToken($loja);
        if (!$token) return false;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post($this->endpoint('ZE_ORDER_CANCEL', ['orderNumber' => $numeroPedido]), [
            'code' => $motivo,
        ]);

        return $response->successful();
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
        ])->post($this->endpoint('ZE_EVENTS_ACK'), $payload);

        return $response->successful();
    }

    // Order Picked — adiciona pedido no app do motoboy
    public function orderPicked(Loja $loja, string $numeroPedido, string $emailMotoboy): bool
    {
        $token = $this->getToken($loja);
        if (!$token) return false;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post($this->endpoint('ZE_LOGISTICS_PICKED', ['orderNumber' => $numeroPedido]), [
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
        ])->post($this->endpoint('ZE_LOGISTICS_START_ROUTE', ['orderNumber' => $numeroPedido]), [
            'email' => $emailMotoboy,
        ]);

        return $response->successful();
    }

    // Finish Delivery
    public function finishDelivery(Loja $loja, string $numeroPedido, string $emailMotoboy, float $lat = 0, float $long = 0): bool
    {
        $token = $this->getToken($loja);
        if (!$token) return false;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post($this->endpoint('ZE_LOGISTICS_FINISH', ['orderNumber' => $numeroPedido]), [
            'email' => $emailMotoboy,
            'lat'   => $lat,
            'long'  => $long,
        ]);

        return $response->successful();
    }

    // Arrived
    public function arrived(Loja $loja, string $numeroPedido, string $emailMotoboy): bool
    {
        $token = $this->getToken($loja);
        if (!$token) return false;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post($this->endpoint('ZE_LOGISTICS_ARRIVED', ['orderNumber' => $numeroPedido]), [
            'email' => $emailMotoboy,
        ]);

        return $response->successful();
    }

    // Cancel Delivery
    public function cancelDelivery(Loja $loja, string $numeroPedido, string $emailMotoboy, string $motivo = 'OTHERS'): bool
    {
        $token = $this->getToken($loja);
        if (!$token) return false;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post($this->endpoint('ZE_LOGISTICS_CANCEL', ['orderNumber' => $numeroPedido]), [
            'email'  => $emailMotoboy,
            'reason' => $motivo,
        ]);

        return $response->successful();
    }

    public function buscarDetalhesEntrega(Loja $loja, string $numeroPedido): ?array
    {
        $token = $this->getToken($loja);
        if (!$token) return null;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->get($this->endpoint('ZE_LOGISTICS_DELIVERY', ['orderNumber' => $numeroPedido]));

        if ($response->failed()) return null;

        return $response->json();
    }
}