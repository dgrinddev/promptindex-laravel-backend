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

    // relation: a prompt belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation: a prompt belongs to a Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relation: a prompt has many images
    public function images()
    {
        return $this->hasMany(Image::class);
    }

    // Relation: a prompt belongs to a cover image (hasOne is not the right one here because the model that owns the foreign key should have belongsTo. The prompt table has the "coverimage_id" foreign key)
    public function coverImage()
    {
        return $this->belongsTo(Image::class, 'coverimage_id');
    }

    protected function categoryId(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value) => $value, // get: Return the value unchanged
            set: fn (null|string|int $value) => $value === '' || is_null($value) ? null : (int) $value,
        );
    }
}
