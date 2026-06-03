<?php

namespace App\Http\Requests;

use App\Models\CarModel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseCarRequest extends FormRequest
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
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $carModel = $this->route('carModel');
            if (! $carModel instanceof CarModel) {
                return;
            }

            $profile = $this->user()?->playerProfile;
            if ($profile === null) {
                $validator->errors()->add('car_model', 'Player profile not found.');

                return;
            }

            foreach ($carModel->purchasabilityErrors($profile) as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($key, $message);
                }
            }
        });
    }
}
