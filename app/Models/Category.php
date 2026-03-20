<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $fillable = [];

    /**
     * Relation: En category kan have mange prompts
     */
    public function prompts()
    {
        return $this->hasMany(Prompt::class);
    }
}
