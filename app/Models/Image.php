<?php

namespace App\Models;

use App\Observers\ImageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([ImageObserver::class])]
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

    // every image belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // every image belongs to one prompt
    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }
}
