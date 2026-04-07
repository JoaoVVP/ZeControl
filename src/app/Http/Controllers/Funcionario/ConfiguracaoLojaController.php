<?php

namespace App\Http\Controllers\Funcionario;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoLoja;
use Illuminate\Http\Request;

class ConfiguracaoLojaController extends Controller
{
    public function index()
    {
        $loja         = auth()->user()->loja;
        $configuracao = ConfiguracaoLoja::firstOrCreate(
            ['loja_id' => $loja->id],
            [
                'pedidos_por_rota'    => 1,
                'modo_emergencia'     => false,
                'gatilho_emergencia'  => 5,
                'auto_start_route'    => false,
                'start_route_minutos' => null,
            ]
        );

        $zeConfigurado = $loja->ze_merchant_id && $loja->ze_client_id && $loja->ze_client_secret;

        return view('funcionario.configuracoes.loja', compact('configuracao', 'loja', 'zeConfigurado'));
    }

    public function update(Request $request)
    {
        $loja  = auth()->user()->loja;

        $rules = [
            'pedidos_por_rota'    => 'required|integer|min:1|max:10',
            'gatilho_emergencia'  => 'required|integer|min:1',
            'start_route_minutos' => 'nullable|integer|min:1',
        ];

        if ($request->filled('ze_merchant_id')) {
            $rules['ze_merchant_id']   = 'required|string';
            $rules['ze_client_id']     = 'required|string';
            $rules['ze_client_secret'] = 'required|string';
        }

        $request->validate($rules);

        if ($request->filled('ze_merchant_id')) {
            $loja->update([
                'ze_merchant_id'   => $request->ze_merchant_id,
                'ze_client_id'     => $request->ze_client_id,
                'ze_client_secret' => $request->ze_client_secret,
            ]);
        }

    ConfiguracaoLoja::updateOrCreate(
        ['loja_id' => $loja->id],
        [
            'pedidos_por_rota'           => $request->pedidos_por_rota,
            'modo_emergencia'            => $request->boolean('modo_emergencia'),
            'gatilho_emergencia'         => $request->gatilho_emergencia,
            'auto_start_route'           => $request->boolean('auto_start_route'),
            'start_route_minutos'        => $request->start_route_minutos,
            'turbo_casa'                 => $request->boolean('turbo_casa'),
            'turbo_prazo_minutos'        => $request->turbo_prazo_minutos,
            'turbo_espera_casa_minutos'  => $request->turbo_espera_casa_minutos,
            'turbo_preferencia'          => $request->boolean('turbo_preferencia'),
            'turbo_casa_modo_emergencia' => $request->boolean('turbo_casa_modo_emergencia'),
        ]
    );

        return back()->with('sucesso', 'Configurações salvas com sucesso!');
    }
    
    public function salvarLocalizacao(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $loja = auth()->user()->loja;

        ConfiguracaoLoja::updateOrCreate(
            ['loja_id' => $loja->id],
            [
                'loja_lat' => $request->lat,
                'loja_lng' => $request->lng,
            ]
        );

        return response()->json(['sucesso' => true]);
    }

    public function excluirLocalizacao()
    {
        $loja = auth()->user()->loja;

        ConfiguracaoLoja::where('loja_id', $loja->id)->update([
            'loja_lat' => null,
            'loja_lng' => null,
        ]);

        return back()->with('aviso', 'Local da loja excluído! Configure um novo local para que os motoboys consigam entrar na fila.');
    }
}