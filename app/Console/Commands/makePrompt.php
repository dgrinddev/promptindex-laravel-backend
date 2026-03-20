<?php

namespace App\Console\Commands;

use App\Models\Prompt;
use App\Models\User;
use Illuminate\Console\Command;

class makePrompt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-prompt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add one extra prompt to user (id 1)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::find(1);

        if (!$user) {
            $this->error('User with id 1 not found.');
            return;
        }

        $prompt = new Prompt([
            'title' => 'Extra Prompt',
            'content' => 'Bla bla bla.',
        ]);

        $prompt->user()->associate($user);
        $prompt->save();

        $this->info("Prompt created with ID: {$prompt->id}");
    }
}