<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nome
 * @property string $email
 * @property string|null $telefone
 * @property string|null $api_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Usuario> $usuarios
 * @property-read int|null $usuarios_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja whereApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja whereNome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja whereTelefone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loja whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Loja extends Model
{
    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'api_token',
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }
}