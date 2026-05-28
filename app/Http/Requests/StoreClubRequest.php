<?php

namespace App\Http\Requests;

use App\Services\ClubService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->clubMember === null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }

        if ($this->has('description')) {
            $this->merge([
                'description' => trim((string) $this->input('description')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:'.config('game.clubs.name_min_length'),
                'max:'.config('game.clubs.name_max_length'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            if ($user === null) {
                return;
            }

            if ($user->clubMember !== null) {
                return;
            }

            $clubService = app(ClubService::class);
            if ($clubService->nameExists((string) $this->input('name'))) {
                $validator->errors()->add('name', 'A club with this name already exists.');
            }
        });
    }
}
