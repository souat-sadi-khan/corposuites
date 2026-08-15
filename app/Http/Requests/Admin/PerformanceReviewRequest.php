<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PerformanceReviewRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'reviewer_id' => 'nullable|exists:employees,id',
            'review_period_start' => 'required|date',
            'review_period_end' => 'required|date|after_or_equal:review_period_start',
            'rating' => 'required|numeric|min:0|max:5',
            'strengths' => 'nullable|string|max:1000',
            'areas_for_improvement' => 'nullable|string|max:1000',
            'goals' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }
}
