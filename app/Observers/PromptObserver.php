<?php

namespace App\Observers;

use App\Models\Prompt;
use Illuminate\Support\Str;

class PromptObserver
{
    /**
     * Handle the Prompt "created" event.
     */
    public function created(Prompt $prompt): void
    {
        //
    }

    /**
     * Handle the Prompt "updated" event.
     */
    public function updated(Prompt $prompt): void
    {
        //
    }

    /**
     * Handle the Prompt "saving" event (when a model is created or updated)
     */
    public function saving(Prompt $prompt): void
    {
        $prompt->excerpt = Str::limit(strip_tags($prompt->content), 250);
    }

    /**
     * Handle the Prompt "deleted" event.
     */
    public function deleted(Prompt $prompt): void
    {
        //
    }

    /**
     * Handle the Prompt "restored" event.
     */
    public function restored(Prompt $prompt): void
    {
        //
    }

    /**
     * Handle the Prompt "force deleted" event.
     */
    public function forceDeleted(Prompt $prompt): void
    {
        //
    }
}
