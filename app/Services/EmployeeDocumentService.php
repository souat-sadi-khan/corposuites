<?php

namespace App\Services;

use App\Models\EmployeeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentService
{
    public function create(array $data, ?UploadedFile $file): EmployeeDocument
    {
        if ($file) {
            $data['file_path'] = $this->storeFile($file);
        }

        return EmployeeDocument::create($data);
    }

    public function update(EmployeeDocument $employeeDocument, array $data, ?UploadedFile $file): EmployeeDocument
    {
        if ($file) {
            $this->deleteFile($employeeDocument->file_path);
            $data['file_path'] = $this->storeFile($file);
        }

        $employeeDocument->update($data);
        return $employeeDocument;
    }

    public function delete(EmployeeDocument $employeeDocument): bool
    {
        $this->deleteFile($employeeDocument->file_path);
        return $employeeDocument->delete();
    }

    protected function storeFile(UploadedFile $file): string
    {
        return $file->store('documents/employees', 'public');
    }

    protected function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
