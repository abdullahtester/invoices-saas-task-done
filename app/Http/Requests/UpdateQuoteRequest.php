<?php

namespace App\Http\Requests;

use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteRequest extends FormRequest
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
     *
     *
     * @return mixed
     */
    public function rules()
    {
        $rules = Quote::$rules;
        $rules['quote_id'] = 'required|unique:quotes,quote_id,'.$this->route('quote')->id;

        return $rules;
    }

    public function messages(): array
    {
        return Quote::$messages;
    }
}
