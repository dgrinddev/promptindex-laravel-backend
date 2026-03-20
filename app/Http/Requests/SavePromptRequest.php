<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class SavePromptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // we handle the authorization in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:100',
                $this->titleUniqueRule(),
            ],
            'type' => [
                'required',
                Rule::in(['text', 'image', 'other']),
            ],
            'category_id' => [
                'nullable',
                'exists:categories,id', // alle brugere har adgang til alle kategorier
            ],
            'content' => [
                'required',
                'string',
                'max:1000000', // max 1 million tegn
            ],
            'coverimage_id' => [
                'nullable',
                Rule::in($this->allowedImageIds()),
            ],
            'upload_image_token' => [
                'nullable',
                'uuid:4',
            ],
        ];
    }

    /**
     * Returnerer unique title-regel afhængigt af store eller update
     */
    protected function titleUniqueRule(): Unique
    {
        if ($this->routeIs('prompts.update')) {
            return Rule::unique('prompts')->ignore($this->route('prompt'));
        }

        // Default: create
        return Rule::unique('prompts');
    }

    /**
     * Hvilke billeder må vælges som cover?
     */
    protected function allowedImageIds(): array
    {
        if ($this->routeIs('prompts.update')) {
            return $this->user()
                ->images()
                ->where('prompt_id', $this->route('prompt')->id)
                ->pluck('id')
                ->all();
        }

        $upload_image_token = $this->input('upload_image_token');

        if (!$upload_image_token) {
            return [];
        }

        return $this->user()
            ->images()
            ->where('upload_image_token', $upload_image_token)
            ->pluck('id')
            ->all();
    }
}