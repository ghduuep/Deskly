<?php

namespace App\Http\Requests\Ticket;

use App\Models\Ticket;
use App\Enum\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCustomer = $this->user()->role === UserRole::Customer;

        return [
            'title' => ['required', 'string', 'max:255'],
            'contact_id' => [
                Rule::requiredIf(! $isCustomer),
                Rule::prohibitedIf($isCustomer),
                'integer',
                Rule::exists('contacts', 'id'),
            ],
        ];
    }
}
