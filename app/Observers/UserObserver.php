<?php

namespace App\Observers;

use App\Models\Image;
use App\Models\Prompt;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Before the user row is removed: delete child rows via Eloquent instead of relying
     * only on foreign-key cascades. Cascades would still remove prompt/image rows in the
     * DB, but would not run PromptObserver or ImageObserver, so files under storage would
     * not be removed (ImageObserver only runs on Eloquent model delete).
     */
    public function deleting(User $user): void
    {
        // Deletes each prompt with Eloquent so PromptObserver::deleting runs and removes
        // that prompt's images via Eloquent → ImageObserver removes disk files.
        $user->prompts()->each(fn(Prompt $prompt) => $prompt->delete());

        // Any images still tied to this user (e.g. uploads without a prompt, or leftovers
        // after the prompt pass). Cascading user delete would drop rows without observers.
        $user->images()->each(fn(Image $image) => $image->delete());
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
