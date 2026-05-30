<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AllocateDriverStatsRequest extends FormRequest
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
            'stat_power' => ['required', 'integer', 'min:0', 'max:255'],
            'stat_acceleration' => ['required', 'integer', 'min:0', 'max:255'],
            'stat_grip' => ['required', 'integer', 'min:0', 'max:255'],
            'stat_handling' => ['required', 'integer', 'min:0', 'max:255'],
        ];
    }

    /**
     * @return array{power: int, acceleration: int, grip: int, handling: int}
     */
    public function increments(): array
    {
        return [
            'power' => (int) $this->input('stat_power'),
            'acceleration' => (int) $this->input('stat_acceleration'),
            'grip' => (int) $this->input('stat_grip'),
            'handling' => (int) $this->input('stat_handling'),
        ];
    }
}
