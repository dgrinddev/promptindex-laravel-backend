<?php

namespace App\Http\Controllers;

use App\Http\Resources\ImageResource;
use App\Models\Image;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ImageController extends Controller
{
	public function store(Request $request)
	{
		abort_if(
				!$request->hasFile('image'),
				400,
				'there is no image'
		);

		$allMyPrompts = $request->user()
				->prompts()
				->pluck('id')
				->all();

		$validated = $request->validate([
				'image' => 'required|image|max:1024|mimes:jpg,jpeg,png',
				'prompt_id' => [
						'nullable',
						Rule::in($allMyPrompts),
				],
				'upload_image_token' => 'nullable|uuid:4',
		]);

		$file = $validated['image'];

		$original_filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

		if (strlen($original_filename) > 255) {
			throw ValidationException::withMessages([
				'image' => ['The filename must not exceed 255 characters.'],
			]);
		}

		$hashed_filename = $file->hashName();
		$path = $file->storeAs('images', $hashed_filename);

		abort_if(
				!$path,
				500,
				'the file could not be saved'
		);

		$extension = $file->extension();
		$size = $file->getSize();

		$image = Image::create([
				'user_id' => $request->user()->id,
				'prompt_id' => $validated['prompt_id'] ?? null,
				'hashed_filename' => $hashed_filename,
				'original_filename' => $original_filename,
				'extension' => $extension,
				'size' => $size,
				'upload_image_token' => $validated['upload_image_token'] ?? null,
		]);

		return response()->json([
				'status' => 'success',
				'message' => 'Image stored successfully',
				'data' => new ImageResource($image),
		], 201);
	}

	public function destroy(Request $request, Image $image)
	{
		if ($request->user()->cannot('delete', $image)) {
				abort(403);
		}
		
		$image->delete();

		return response(null, 204);
	}
}