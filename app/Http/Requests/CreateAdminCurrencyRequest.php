<?php

namespace App\Http\Requests;

use App\Models\AdminCurrency;
use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;

class CreateAdminCurrencyRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return AdminCurrency::$rules;
    }
}
