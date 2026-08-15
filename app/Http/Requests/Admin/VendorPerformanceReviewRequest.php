<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VendorPerformanceReviewRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'reviewed_by' => ['nullable', 'exists:admins,id'],
            'review_period_start' => ['required', 'date'],
            'review_period_end' => ['required', 'date', 'after_or_equal:review_period_start'],
            'quality_rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'delivery_rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'pricing_rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'communication_rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }
}
