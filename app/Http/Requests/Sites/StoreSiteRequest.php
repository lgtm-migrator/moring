<?php

namespace App\Http\Requests\Sites;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
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
        return [
            'name' => 'required | string | min:3 | max:128',
            'url' => 'required | string | min:3 | max:128',
            'active' => 'integet | max:1',
            'https' => 'integer | max:1',
            'comment' => 'max:255',
        ];
    }

    public function messages()
    {
        return [
          'name.required' => 'Поле "Название сайта" должно быть заполнено',
          'url.required' => 'Поле "Адрес URL" должно быть заполнено',
          'name.min' => 'Минимальная длина записи в поле "Название сайта" 3 символа',
          'url.min' => 'Минимальная длина записи в поле "Адрес URL" 3 символа',
          'name.max' => 'Максимальная длина записи в поле "Название сайта" 128 символов',
          'url.max' => 'Максимальная длина записи в поле "Адрес URL" 128 символов',
          'comment.max' => 'Максимальная длина записи в поле "Описание" 255 символов',
        ];
    }
}
