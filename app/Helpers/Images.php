<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Storage;

class Images
{
    public static function employeeAvatar(?\App\Models\Employee $employee): string
    {
        $photo = $employee?->photo;
        $isPlaceholder = $photo && basename(str_replace('\\', '/', $photo)) === 'default-avatar.png';
        if ($photo && !$isPlaceholder && is_file(public_path($photo))) {
            return '<img src="' . e(asset($employee->photo)) . '" alt="' . e($employee->full_name) . '" loading="lazy">';
        }

        $initials = mb_strtoupper(
            mb_substr(trim($employee?->first_name ?? ''), 0, 1) .
            mb_substr(trim($employee?->last_name ?? ''), 0, 1)
        );
        // Choose a fresh gradient whenever the table renders the avatar.
        $hue = random_int(0, 359);
        $endHue = ($hue + 45) % 360;

        return '<span class="employee-avatar-initials" style="--avatar-hue: ' . $hue . '; --avatar-end-hue: ' . $endHue . ';" role="img" aria-label="' . e($employee?->full_name ?? 'Unknown employee') . '">' . e($initials ?: '?') . '</span>';
    }

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
            return '<img src="' . asset($path) . '" alt="Image ' . $path . '" style="width:50px;">';
        } else {
            $placeholder = 'assets/system/images/default-avatar.png';
            return '<img src="' . asset($placeholder) . '" alt="Placeholder Image" style="width:50px;">';
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
