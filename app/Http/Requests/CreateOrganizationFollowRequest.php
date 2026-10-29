<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProfileType;
use App\Models\Profile;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

final class CreateOrganizationFollowRequest extends FormRequest
{
    /**
     * Profiles are users' too, who are followed as users instead.
     */
    public function authorize(): Response
    {
        $organization = $this->route('organization');
        assert($organization instanceof Profile);

        return $organization->type === ProfileType::Organization ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * @return array{}
     */
    public function rules(): array
    {
        return [];
    }
}
