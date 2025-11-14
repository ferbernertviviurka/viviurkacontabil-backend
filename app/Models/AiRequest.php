<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'uuid',
        'conversation_uuid',
        'tipo',
        'prompt',
        'response',
        'tokens_used',
        'cost',
        'model',
        'provider',
        'context',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the user that owns the AI request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
