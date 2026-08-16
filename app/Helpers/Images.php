<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Storage;

class Images
{
    public static function upload($folder, $image)
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

        $originalName = str_replace(' ', '-', $originalName);

        $fileName = $originalName . '.' . $image->getClientOriginalExtension();

        $image->storeAs('images/' . $folder, $fileName, 'public'); // Specify 'public' disk

        $productImage = 'storage/images/' . $folder . '/' . $fileName;
        return $productImage;
    }

    public static function delete($image)
    {
        if (file_exists(public_path($image))) {
            unlink(public_path($image));
            return true;
        }
    }

    public static function show($path)
    {
        if ($path && file_exists(public_path($path))) {
            return '<img src="' . asset($path) . '" alt="Image ' . $path . '" style="width:70px;">';
        } else {
            $placeholder = 'assets/images/placeholder.jpg';
            return '<img src="' . asset($placeholder) . '" alt="Placeholder Image" style="width:70px;">';
        }
    }


    public static function update($folder, $oldImagePath, $newImage)
    {
        if($oldImagePath) {
            self::delete($oldImagePath);
        }

        $fileName = time() . rand(100, 999) . '.' . $newImage->getClientOriginalExtension();

        $newImage->storeAs('images/' . $folder, $fileName, 'public');

        $productImage = 'storage/images/' . $folder . '/' . $fileName;

        return $productImage;
    }
}
