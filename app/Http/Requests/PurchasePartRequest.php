<?php

namespace App\Http\Requests;

use App\Models\PartModel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PurchasePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $partModel = $this->route('partModel');
        if ($partModel instanceof PartModel) {
            $this->merge([
                '_purchase_target' => $partModel->id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $partModel = $this->route('partModel');
            if (! $partModel instanceof PartModel) {
                return;
            }

            $profile = $this->user()?->playerProfile;
            if ($profile === null) {
                $validator->errors()->add('part_model', 'Player profile not found.');

                return;
            }

            foreach ($partModel->purchasabilityErrors($profile) as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($key, $message);
                }
            }
        });
    }
}
