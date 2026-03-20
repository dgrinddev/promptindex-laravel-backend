<?php

namespace App\Models;

use App\Observers\PromptObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([PromptObserver::class])]
class Prompt extends Model
{
    /** @use HasFactory<\Database\Factories\PromptFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'content',
        'category_id',
        'coverimage_id',
    ];

    // Relation: en prompt tilhører en bruger
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation: en prompt tilhører en Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relation: en prompt har mange images
    public function images()
    {
        return $this->hasMany(Image::class);
    }

    // Relation: en prompt tilhører et cover-image (hasOne er ikke det rette her fordi den model der ejer foreign key skal have belongsTo. prompt-tabellen har "coverimage_id"-foreign key)
    public function coverImage()
    {
        return $this->belongsTo(Image::class, 'coverimage_id');
    }

    protected function categoryId(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value) => $value, // get: Returnér værdien uændret
            set: fn (null|string|int $value) => $value === '' || is_null($value) ? null : (int) $value,
        );
    }
}
