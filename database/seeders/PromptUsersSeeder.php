<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Image;
use App\Models\Prompt;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Observers\PromptObserver;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PromptUsersSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoriesData = $this->seedCategories();

        $usersData = collect(
            json_decode(file_get_contents(base_path('database/data/users.json')), true)
        );

        $appNameSlug = Str::slug(config('app.name'));

        foreach ($usersData as $userItem) {
            $userCreatedAt = $this->randomDateBetween('-7 days', '-6 days');

            $user = User::factory()->create([
                'name' => $userItem['name'],
                'email' => strtolower($userItem['name']) . "@{$appNameSlug}.io",
                'password' => Hash::make(env('PROMPTUSERS_PASSWORD')),
                'created_at' => $userCreatedAt,
                'updated_at' => $this->randomDateBetween($userCreatedAt, '-5 days'),
            ]);
            
            $promptsData = collect(
                json_decode(file_get_contents(base_path('database/data/prompt-users/' . $userItem['name'] . '/prompts.json')), true)
            )->map(function ($item) {
                $item['images'] = collect($item['images']);
                return $item;
            });
            
            foreach ($promptsData as $p) {
                $this->seedPrompt($user, $p, $categoriesData);
            }
        }
    }

    /**
     * Seed categories from JSON and return the raw data for later lookups.
     */
    private function seedCategories(): Collection
    {
        $categoriesData = collect(
            json_decode(file_get_contents(base_path('database/data/categories.json')), true)
        );

        foreach ($categoriesData as $cat) {
            $categoryCreatedAt = $this->randomDateBetween('-10 days', '-9 days');

            Category::factory()->create([
                'name' => $cat['name'],
                'description' => $cat['description'],
                'slug' => Str::slug($cat['name']),
                'created_at' => $categoryCreatedAt,
                'updated_at' => $this->randomDateBetween($categoryCreatedAt, '-8 days'),
            ]);
        }

        return $categoriesData;
    }

    /**
     * Create a single prompt (with all its images)
     */
    private function seedPrompt(User $user, array $p, Collection $categoriesData): void
    {
        $category = null;
        $categoryKey = $p['category_key'];

        if (!empty($categoryKey)) {
            $categoryItem = $categoriesData->firstWhere('key', $categoryKey);
            if ($categoryItem) {
                $categorySlug = Str::slug($categoryItem['name']);
                $category = Category::where('slug', $categorySlug)->first();
            }
        }

        $promptCreatedAt = $this->randomDateBetween($user->created_at, '-5 days');
        $promptUpdatedAt = $this->randomDateBetween($promptCreatedAt, '-4 days');

        $prompt = Prompt::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category?->id,
            'title' => $p['title'],
            'type' => $p['type'],
            'content' => $p['content'],
            'created_at' => $promptCreatedAt,
            'updated_at' => $promptUpdatedAt,
        ]);

        $observer = new PromptObserver();
        $observer->saving($prompt);
        $prompt->save();

        $images = $p['images']->values();
        $imageCount = $images->count();

        if ($imageCount === 0) {
            return;
        }

        $totalSeconds = $promptUpdatedAt->timestamp - $promptCreatedAt->timestamp;
        $secondsPerImage = (int) floor($totalSeconds / $imageCount);

        if ($secondsPerImage < 10) {
            throw new RuntimeException(
                "Prompt '{$p['title']}' has {$imageCount} images but only {$totalSeconds} seconds between created_at and updated_at. This gives {$secondsPerImage} seconds per image; at least 10 seconds are required."
            );
        }

        foreach ($images as $index => $img) {
            $filePath = base_path("database/data/prompt-users/{$user->name}/prompt-images/{$p['key']}__{$img['original_filename']}");

            if (!is_file($filePath)) {
                throw new RuntimeException("Image file does not exist: {$filePath}");
            }

            $file = new File($filePath);
            $hashedFilename = $file->hashName();
            $storedPath = Storage::disk('public')->putFileAs('images', $file, $hashedFilename);

            if (!$storedPath) {
                throw new RuntimeException("Failed to store image: {$filePath}");
            }

            $imageCreatedAt = $promptCreatedAt->copy()->addSeconds(
                ($index * $secondsPerImage) + random_int(0, $secondsPerImage - 1)
            );

            $image = Image::factory()->create([
                'user_id' => $user->id,
                'prompt_id' => $prompt->id,
                'hashed_filename' => $hashedFilename,
                'original_filename' => pathinfo($img['original_filename'], PATHINFO_FILENAME),
                'extension' => $file->extension(),
                'size' => $file->getSize(),
                'upload_image_token' => null,
                'created_at' => $imageCreatedAt,
                'updated_at' => $imageCreatedAt,
            ]);

            if ($img['is_cover'] && !$prompt->coverimage_id) {
                $prompt->coverimage_id = $image->id;
                $prompt->save();
            }
        }
    }

    private function randomDateBetween(DateTimeInterface|string $from, DateTimeInterface|string $to): Carbon
    {
        $start = $from instanceof DateTimeInterface ? Carbon::instance($from) : Carbon::parse($from);
        $end = $to instanceof DateTimeInterface ? Carbon::instance($to) : Carbon::parse($to);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return Carbon::createFromTimestamp(
            random_int($start->timestamp, $end->timestamp),
            config('app.timezone')
        );
    }
}