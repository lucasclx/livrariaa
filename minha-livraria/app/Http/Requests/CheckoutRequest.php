<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome_cliente' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZÀ-ÿ\s]+$/'
            ],
            'email_cliente' => [
                'required',
                'email',
                'max:255'
            ],
            'telefone_cliente' => [
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^[\d\s\(\)\-\+]+$/'
            ],
            'endereco_entrega' => [
                'required',
                'string',
                'min:10',
                'max:500'
            ],
            'cidade' => [
                'required',
                'string',
                'min:2',
                'max:100'
            ],
            'estado' => [
                'required',
                'string',
                'min:2',
                'max:2'
            ],
            'cep' => [
                'required',
                'string',
                'regex:/^\d{5}-?\d{3}$/'
            ],
            'forma_pagamento' => [
                'required',
                'in:cartao_credito,cartao_debito,pix,boleto'
            ],
            'observacoes' => [
                'nullable',
                'string',
                'max:1000'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome_cliente.required' => 'O nome completo é obrigatório.',
            'nome_cliente.min' => 'O nome deve ter pelo menos 2 caracteres.',
            'nome_cliente.regex' => 'O nome deve conter apenas letras e espaços.',
            
            'email_cliente.required' => 'O e-mail é obrigatório.',
            'email_cliente.email' => 'Por favor, digite um e-mail válido.',
            
            'telefone_cliente.required' => 'O telefone é obrigatório.',
            'telefone_cliente.min' => 'O telefone deve ter pelo menos 10 dígitos.',
            'telefone_cliente.regex' => 'O telefone deve conter apenas números, parênteses, hífens e espaços.',
            
            'endereco_entrega.required' => 'O endereço de entrega é obrigatório.',
            'endereco_entrega.min' => 'O endereço deve ter pelo menos 10 caracteres.',
            
            'cidade.required' => 'A cidade é obrigatória.',
            'cidade.min' => 'A cidade deve ter pelo menos 2 caracteres.',
            
            'estado.required' => 'O estado é obrigatório.',
            'estado.size' => 'O estado deve ter exatamente 2 caracteres (ex: SP, RJ).',
            
            'cep.required' => 'O CEP é obrigatório.',
            'cep.regex' => 'O CEP deve estar no formato 12345-678 ou 12345678.',
            
            'forma_pagamento.required' => 'Por favor, selecione uma forma de pagamento.',
            'forma_pagamento.in' => 'Forma de pagamento inválida.',
            
            'observacoes.max' => 'As observações não podem exceder 1000 caracteres.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome_cliente' => 'nome completo',
            'email_cliente' => 'e-mail',
            'telefone_cliente' => 'telefone',
            'endereco_entrega' => 'endereço de entrega',
            'forma_pagamento' => 'forma de pagamento'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome_cliente' => trim($this->nome_cliente),
            'email_cliente' => strtolower(trim($this->email_cliente)),
            'telefone_cliente' => preg_replace('/[^0-9]/', '', $this->telefone_cliente),
            'cep' => preg_replace('/[^0-9]/', '', $this->cep),
            'estado' => strtoupper(trim($this->estado ?? '')),
        ]);
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Formatar dados após validação
        $this->merge([
            'telefone_cliente' => $this->formatarTelefone($this->telefone_cliente),
            'cep' => $this->formatarCep($this->cep),
        ]);
    }

    /**
     * Formatar telefone no padrão brasileiro
     */
    private function formatarTelefone(string $telefone): string
    {
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        
        if (strlen($telefone) == 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
        } elseif (strlen($telefone) == 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
        }
        
        return $telefone;
    }

    /**
     * Formatar CEP no padrão brasileiro
     */
    private function formatarCep(string $cep): string
    {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        
        if (strlen($cep) == 8) {
            return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);
        }
        
        return $cep;
    }
}