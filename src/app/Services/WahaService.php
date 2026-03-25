<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    private string $url;
    private string $session;
    private string $apiKey;

    public function __construct()
    {
        $this->url     = env('WAHA_URL', 'http://waha:3000');
        $this->session = env('WAHA_SESSION', 'default');
        $this->apiKey  = env('WAHA_KEY', 'zecontrol_wpp_key');
    }

    public function enviarMensagem(string $numero, string $mensagem): bool
    {
        try {
            $chatId = $this->formatarNumero($numero);

            Log::info('WAHA enviando mensagem', [
                'numero_original' => $numero,
                'chatId'          => $chatId,
            ]);

            $response = Http::timeout(10)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->post("{$this->url}/api/sendText", [
                    'session' => $this->session,
                    'chatId'  => $chatId,
                    'text'    => $mensagem,
                ]);

            if ($response->failed()) {
                Log::error('WAHA erro ao enviar mensagem', [
                    'numero'   => $numero,
                    'chatId'   => $chatId,
                    'status'   => $response->status(),
                    'response' => $response->json(),
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('WAHA exception', [
                'numero' => $numero,
                'erro'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function formatarNumero(string $numero): string
    {
        return $numero . '@c.us';
    }
}