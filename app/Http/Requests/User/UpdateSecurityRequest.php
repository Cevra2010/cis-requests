<?php

namespace App\Http\Requests\User;

use App\Http\Logic\CisAccess\Facades\Access;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSecurityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Access::hasAccess("user.edit.base");
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'password' => 'confirmed|min:8|max:52',
            'password_confirmation' => 'required',
        ];
    }
}
