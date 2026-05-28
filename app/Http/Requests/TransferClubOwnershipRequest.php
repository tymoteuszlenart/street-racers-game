<?php

namespace App\Http\Requests;

use App\Models\Club;
use App\Models\ClubMember;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TransferClubOwnershipRequest extends FormRequest
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
            'member_id' => ['required', 'integer', 'exists:club_members,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $club = $this->route('club');
            if (! $club instanceof Club) {
                return;
            }

            $member = ClubMember::query()->find($this->input('member_id'));
            if ($member === null || $member->club_id !== $club->id) {
                $validator->errors()->add('member_id', 'Member not found in this club.');
            }
        });
    }

    public function targetMember(): ClubMember
    {
        return ClubMember::query()->findOrFail($this->integer('member_id'));
    }
}
