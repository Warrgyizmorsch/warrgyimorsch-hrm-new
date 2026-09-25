<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    // Files live on the private "local" disk (storage/app/private) — Aadhaar/PAN must never be
    // publicly reachable, so they are only served through EmployeeDocumentController::download.
    public const DISK = 'local';

    // type => [label, validation rule, bootstrap icon]
    public const TYPES = [
        'aadhaar' => ['label' => 'Aadhaar Card', 'rules' => 'file|mimes:pdf,png,jpg,jpeg,webp|max:5120', 'icon' => 'bi-file-earmark-person'],
        'pan' => ['label' => 'PAN Card', 'rules' => 'file|mimes:pdf,png,jpg,jpeg,webp|max:5120', 'icon' => 'bi-file-earmark-text'],
        'offer_letter' => ['label' => 'Offer Letter', 'rules' => 'file|mimes:pdf,doc,docx|max:10240', 'icon' => 'bi-file-earmark-richtext'],
    ];

    protected $fillable = [
        'employee_id',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getLabelAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? ucwords(str_replace('_', ' ', $this->type));
    }

    public static function validationRules(): array
    {
        $rules = ['documents' => 'nullable|array'];
        foreach (self::TYPES as $type => $meta) {
            $rules["documents.$type"] = 'nullable|' . $meta['rules'];
        }
        return $rules;
    }

    public static function validationAttributes(): array
    {
        $attributes = [];
        foreach (self::TYPES as $type => $meta) {
            $attributes["documents.$type"] = $meta['label'];
        }
        return $attributes;
    }

    /**
     * Store (or replace) one document of the given type for an employee.
     * The old file is removed only after the new one is saved.
     */
    public static function storeFor(Employee $employee, string $type, UploadedFile $file): self
    {
        $path = $file->store("employee-documents/{$employee->id}", self::DISK);

        $existing = self::where('employee_id', $employee->id)->where('type', $type)->first();
        $oldPath = $existing?->file_path;

        $document = self::updateOrCreate(
            ['employee_id' => $employee->id, 'type' => $type],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]
        );

        if ($oldPath && $oldPath !== $path) {
            Storage::disk(self::DISK)->delete($oldPath);
        }

        return $document;
    }
}
