<?php

namespace App\Services;

use App\Models\PerformanceReview;

class PerformanceReviewService
{
    public function create(array $data): PerformanceReview
    {
        return PerformanceReview::create($data);
    }

    public function update(PerformanceReview $performanceReview, array $data): PerformanceReview
    {
        $performanceReview->update($data);
        return $performanceReview;
    }

    public function delete(PerformanceReview $performanceReview): bool
    {
        return $performanceReview->delete();
    }
}
