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
     * Prompts inserted last to control what appears on the first page of
     * promptindex.io/prompts/prompt-library.
     *
     * These are chosen to ensure a good representation of:
     * - different users
     * - categories
     * - prompt types
     *
     * As well as edge cases:
     * - 0 images
     * - multiple images (2+ and many)
     * - no category
     * - 1 image without cover (lands first on page 2)
     *
     * Selection overview:
     *
     * | Order | Key             | Images    | Type  | Category     | User        |
     * | ----- | --------------- | --------- | ----- | ------------ | ----------- |
     * | 1     | promptforge-023 | 4 images  | image | business     | promptforge |
     * | 2     | devnotes-009    | 12 images | text  | productivity | devnotes    |
     * | 3     | pixel-sora-019  | 1 image   | text  | none         | pixel-sora  |
     * | 4     | classmind-009   | 12 images | text  | productivity | classmind   |
     * | 5     | growthgrid-013  | 2 images  | image | email        | growthgrid  |
     * | 6     | pixel-sora-020  | 1 image   | other | writing      | pixel-sora  |
     * | 7     | classmind-003   | 1 image   | image | social media | classmind   |
     * | 8     | devnotes-004    | 0 images  | text  | email        | devnotes    |
     * | 9     | promptforge-024 | 1 image   | other | writing      | promptforge |
     * | 10    | pixel-sora-018  | 3 images  | image | social media | pixel-sora  |
     * | 11    | growthgrid-012  | 2 images  | text  | marketing    | growthgrid  |
     * | 12    | classmind-012   | 1 image   | other | research     | classmind   |
     * | 13    | promptforge-001 | 1 image*  | text  | writing      | promptforge |
     *
     * Note: `*` indicates 1 image without cover
     * 
     * Ordered newest-first.
     * The first item ends up with the latest created_at timestamp,
     * the last item ends up with the earliest timestamp in the late range.
     */
    private array $latePromptKeys = [
        'promptforge-023',
        'devnotes-009',
        'pixel-sora-019',
        'classmind-009',
        'growthgrid-013',
        'pixel-sora-020',
        'classmind-003',
        'devnotes-004',
        'promptforge-024',
        'pixel-sora-018',
        'growthgrid-012',
        'classmind-012',
        'promptforge-001',
    ];

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

        // Collect each user object alongside its full prompt data so we can
        // look up late prompts by key in the second pass below.
        $userPromptMap = []; // [ 'promptforge' => ['user' => User, 'prompts' => Collection] ]

        // foreach user: create user, load their prompts from JSON, and store in $userPromptMap for later seeding.
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
                json_decode(file_get_contents(base_path('database/data/prompt-users/' . $user->name . '/prompts.json')), true)
            )->map(function ($item) {
                $item['images'] = collect($item['images']);
                return $item;
            });
            
            $userPromptMap[$user->name] = [
                'user' => $user,
                'prompts' => $promptsData,
            ];
        }

        // First pass: seed only the normal (non-late) prompts for this user.
        foreach ($userPromptMap as $data) {
            foreach ($data['prompts'] as $p) {
                if (!in_array($p['key'], $this->latePromptKeys)) {
                    $this->seedPrompt($data['user'], $p, $categoriesData);
                }
            }
        }

        // Second pass: seed late prompts in reverse list order so that the
        // last item in $latePromptKeys gets the oldest created_at timestamp and the first
        // item gets the newest created_at timestamp within the -4 days to -3 days range.
        $reversedKeys  = array_reverse($this->latePromptKeys);
        $lateCount     = count($reversedKeys);
        $rangeStart    = Carbon::parse('-4 days');
        $rangeEnd      = Carbon::parse('-3 days');
        $totalSeconds  = $rangeEnd->timestamp - $rangeStart->timestamp; // 86400 seconds in 1 day
        $secondsPerSlot = (int) floor($totalSeconds / $lateCount);

        foreach ($reversedKeys as $index => $key) {
            // Find the user who owns this prompt key.
            foreach ($userPromptMap as $data) {
                $promptData = $data['prompts']->firstWhere('key', $key);

                if ($promptData) {
                    // Spread timestamps evenly, with a small random jitter inside each slot —
                    // same technique used for $imageCreatedAt.
                    $forcedCreatedAt = $rangeStart->copy()->addSeconds(
                        ($index * $secondsPerSlot) + random_int(0, $secondsPerSlot - 1)
                    );
                    // updated_at is a random moment between created_at and -2 days.
                    $forcedUpdatedAt = $this->randomDateBetween($forcedCreatedAt, '-2 days');

                    $this->seedPrompt($data['user'], $promptData, $categoriesData, $forcedCreatedAt, $forcedUpdatedAt);
                    break;
                }
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
     * Create a single prompt (with all its images).
     * $forcedCreatedAt and $forcedUpdatedAt are used by the late-prompt pass (second pass)
     * to pin timestamps to the -4 days / -3 days range instead of the default range.
     */
    private function seedPrompt(User $user, array $p, Collection $categoriesData, ?Carbon $forcedCreatedAt = null, ?Carbon $forcedUpdatedAt = null): void
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

        // Use forced timestamps when provided (late-prompt pass), otherwise
        // fall back to the original random ranges.
        $promptCreatedAt = $forcedCreatedAt ?? $this->randomDateBetween($user->created_at, '-5 days');
        $promptUpdatedAt = $forcedUpdatedAt ?? $this->randomDateBetween($promptCreatedAt, '-4 days');

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