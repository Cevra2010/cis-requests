<?php

namespace App\Http\Requests\Source;

use App\Models\ProductSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                Rule::unique('product_sources')->ignore($this->source->cis_row_id, 'cis_row_id')
            ],
        ];
    }
}
