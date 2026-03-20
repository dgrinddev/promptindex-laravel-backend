<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePromptRequest;
use App\Http\Resources\PromptResource;
use App\Http\Resources\PromptEditResource;
use App\Models\Prompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PromptController extends Controller
{
    /**
     * Display a listing of all public prompts
     */
    public function index()
    {
        $prompts = Prompt::with(['images', 'category', 'user'])
            ->where('status', 'public')
            ->latest()
            ->paginate(12);
        return PromptResource::collection($prompts);
    }

    /**
     * Display a listing of all the prompts of the currently authenticated user
     */
    public function myPrompts(Request $request)
    {
        $prompts = $request->user()
            ->prompts()
            ->with(['images', 'category', 'user'])
            ->latest()
            ->paginate(12);
        return PromptResource::collection($prompts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SavePromptRequest $request)
    {
        if ($request->user()->cannot('create', Prompt::class)) {
            abort(403);
        }

        $prompt = $this->savePrompt($request, new Prompt);

        return response()->json([
            'status' => 'success',
            'message' => 'Prompt created successfully',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Prompt $prompt)
    {
        Gate::authorize('view', $prompt);
        return $prompt->load('images', 'category', 'user')->toResource();
    }

    /**
     * Get the specified resource with specific fields.
     */
    public function edit(Request $request, Prompt $prompt)
    {
        Gate::authorize('update', $prompt);
        return new PromptEditResource($prompt->load('images'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SavePromptRequest $request, Prompt $prompt)
    {
        if ($request->user()->cannot('update', $prompt)) {
            abort(403);
        }

        $prompt = $this->savePrompt($request, $prompt);

        return response()->json([
            'status' => 'success',
            'message' => 'Prompt updated successfully',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Prompt $prompt)
    {
        if ($request->user()->cannot('delete', $prompt)) {
            abort(403);
        }
        
        $prompt->delete();

        return response(null, 204);
    }

    /**
     * Save prompt
     */
    protected function savePrompt(SavePromptRequest $request, Prompt $prompt): Prompt
    {
        $prompt->fill(
            $request->safe()->only([
                'title',
                'type',
                'category_id',
                'content',
                'coverimage_id',
            ])
        );

        $prompt->user_id ??= $request->user()->id;

        $prompt->save();

        if (
            $prompt->wasRecentlyCreated
            && $request->safe()->filled('upload_image_token')
        ) {
            $request->user()
                ->images()
                ->where('upload_image_token', $request->safe()->input('upload_image_token'))
                ->update([
                    'prompt_id' => $prompt->id,
                    'upload_image_token' => null,
                ]);
        }

        return $prompt;
    }
}