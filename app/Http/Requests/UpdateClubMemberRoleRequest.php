<?php

namespace App\Http\Requests;

use App\Models\ClubMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClubMemberRoleRequest extends FormRequest
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
            'action' => ['required', 'string', Rule::in(['promote', 'demote'])],
        ];
    }

    public function targetMember(): ClubMember
    {
        $member = $this->route('member');
        if (! $member instanceof ClubMember) {
            abort(404);
        }

        return $member;
    }
}
