<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MailSettingsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_enabled' => ['boolean'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'encryption' => ['required', 'in:tls,ssl,none'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1024'],
            'from_address' => ['required', 'email', 'max:254'],
            'from_name' => ['required', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'host' => 'servidor',
            'port' => 'puerto',
            'encryption' => 'cifrado',
            'username' => 'usuario',
            'password' => 'contraseña',
            'from_address' => 'correo remitente',
            'from_name' => 'nombre remitente',
        ];
    }
}
