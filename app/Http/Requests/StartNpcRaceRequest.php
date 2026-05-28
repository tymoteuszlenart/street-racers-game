<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartNpcRaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function idempotencyKey(): string
    {
        return $this->validated('idempotency_key');
    }
}
