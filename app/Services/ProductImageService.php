<?php

namespace App\Services;

use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    public function create(array $data, ?UploadedFile $file): ProductImage
    {
        if ($file) {
            $data['image_path'] = $this->storeFile($file);
        }

        $productImage = ProductImage::create($data);

        if ($productImage->is_primary) {
            $this->unsetOtherPrimaryImages($productImage);
        }

        return $productImage;
    }

    public function update(ProductImage $productImage, array $data, ?UploadedFile $file): ProductImage
    {
        if ($file) {
            $this->deleteFile($productImage->image_path);
            $data['image_path'] = $this->storeFile($file);
        }

        $productImage->update($data);

        if ($productImage->is_primary) {
            $this->unsetOtherPrimaryImages($productImage);
        }

        return $productImage;
    }

    public function delete(ProductImage $productImage): bool
    {
        $this->deleteFile($productImage->image_path);
        return $productImage->delete();
    }

    protected function unsetOtherPrimaryImages(ProductImage $productImage): void
    {
        ProductImage::where('product_id', $productImage->product_id)
            ->where('id', '!=', $productImage->id)
            ->update(['is_primary' => false]);
    }

    protected function storeFile(UploadedFile $file): string
    {
        return $file->store('products/images', 'public');
    }

    protected function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
