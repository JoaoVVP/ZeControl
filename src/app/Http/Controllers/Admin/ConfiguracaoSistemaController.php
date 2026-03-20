<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConfiguracaoSistemaController extends Controller
{
    public function index()
    {
        $wahaUrl     = env('WAHA_URL', 'http://waha:3000');
        $wahaKey     = env('WAHA_KEY', 'zecontrol_wpp_key');
        $wahaSession = env('WAHA_SESSION', 'default');

        try {
            $response   = Http::withHeaders(['X-Api-Key' => $wahaKey])
                ->get("{$wahaUrl}/api/sessions/{$wahaSession}");
            $wahaStatus = $response->json('status') ?? 'DESCONHECIDO';
        } catch (\Exception $e) {
            $wahaStatus = 'ERRO';
        }

        $telefone = ConfiguracaoSistema::get('waha_telefone');

        return view('admin.configuracoes.sistema', compact('wahaStatus', 'telefone'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'telefone' => 'required|string',
        ]);

        ConfiguracaoSistema::set('waha_telefone', $request->telefone);

        return back()->with('sucesso', 'Configurações salvas!');
    }

public function gerarQrCode()
{
    $wahaUrl     = env('WAHA_URL', 'http://waha:3000');
    $wahaKey     = env('WAHA_KEY', 'zecontrol_wpp_key');
    $wahaSession = env('WAHA_SESSION', 'default');

    try {
        // Para sessão existente se houver
        Http::withHeaders(['X-Api-Key' => $wahaKey])
            ->post("{$wahaUrl}/api/sessions/{$wahaSession}/stop");

        // Inicia nova sessão
        $response = Http::withHeaders(['X-Api-Key' => $wahaKey])
            ->post("{$wahaUrl}/api/sessions/start", [
                'name' => $wahaSession,
            ]);

        if ($response->successful()) {
            return response()->json(['sucesso' => true]);
        }

        return response()->json(['sucesso' => false]);

    } catch (\Exception $e) {
        return response()->json(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
}

    public function qrCodeImagem()
    {
        $wahaUrl     = env('WAHA_URL', 'http://waha:3000');
        $wahaKey     = env('WAHA_KEY', 'zecontrol_wpp_key');
        $wahaSession = env('WAHA_SESSION', 'default');

        try {
            $response = Http::withHeaders(['X-Api-Key' => $wahaKey])
                ->get("{$wahaUrl}/api/{$wahaSession}/auth/qr");

            if ($response->successful()) {
                return response($response->body(), 200)
                    ->header('Content-Type', 'image/png')
                    ->header('Cache-Control', 'no-cache, no-store');
            }

            return response('QR Code não disponível', 404);

        } catch (\Exception $e) {
            return response('Erro', 500);
        }
    }

    public function desconectar()
    {
        $wahaUrl     = env('WAHA_URL', 'http://waha:3000');
        $wahaKey     = env('WAHA_KEY', 'zecontrol_wpp_key');
        $wahaSession = env('WAHA_SESSION', 'default');

        try {
            Http::withHeaders(['X-Api-Key' => $wahaKey])
                ->post("{$wahaUrl}/api/sessions/{$wahaSession}/stop");

            Http::withHeaders(['X-Api-Key' => $wahaKey])
                ->delete("{$wahaUrl}/api/sessions/{$wahaSession}");

            return back()->with('sucesso', 'WhatsApp desconectado com sucesso!');
        } catch (\Exception $e) {
            return back()->withErrors(['erro' => 'Erro ao desconectar.']);
        }
    }

    public function statusWaha()
    {
        $wahaUrl     = env('WAHA_URL', 'http://waha:3000');
        $wahaKey     = env('WAHA_KEY', 'zecontrol_wpp_key');
        $wahaSession = env('WAHA_SESSION', 'default');

        try {
            $response = Http::withHeaders(['X-Api-Key' => $wahaKey])
                ->get("{$wahaUrl}/api/sessions/{$wahaSession}");

            return response()->json([
                'status' => $response->json('status') ?? 'DESCONHECIDO',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERRO']);
        }
    }
}