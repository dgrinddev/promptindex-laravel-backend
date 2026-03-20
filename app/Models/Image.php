<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'prompt_id',
        'hashed_filename',
        'original_filename',
        'extension',
        'size',
        'upload_image_token',
    ];

    // Hver image tilhører én bruger
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Hver image tilhører én prompt
    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }
}
