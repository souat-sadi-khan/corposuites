<?php

namespace App\Services;

use App\Models\VendorPerformanceReview;

class VendorPerformanceReviewService
{
    public function create(array $data): VendorPerformanceReview
    {
        $data['overall_rating'] = $this->calculateOverallRating($data);

        return VendorPerformanceReview::create($data);
    }

    public function update(VendorPerformanceReview $vendorPerformanceReview, array $data): VendorPerformanceReview
    {
        $data['overall_rating'] = $this->calculateOverallRating($data);

        $vendorPerformanceReview->update($data);

        return $vendorPerformanceReview;
    }

    public function delete(VendorPerformanceReview $vendorPerformanceReview): bool
    {
        return $vendorPerformanceReview->delete();
    }

    protected function calculateOverallRating(array $data): float
    {
        $ratings = [
            (float) $data['quality_rating'],
            (float) $data['delivery_rating'],
            (float) $data['pricing_rating'],
            (float) $data['communication_rating'],
        ];

        return round(array_sum($ratings) / count($ratings), 1);
    }
}
