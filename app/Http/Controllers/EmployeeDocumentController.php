<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    private const ADMIN_ROLES = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head'];

    /**
     * View/download a document. Admin roles can open any employee's documents;
     * everyone else only their own.
     */
    public function download($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        $user = auth()->user();

        if (!$this->isAdmin() && (int) $user->employee_id !== (int) $document->employee_id) {
            abort(403);
        }

        $disk = Storage::disk(EmployeeDocument::DISK);
        if (!$disk->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        // Open PDFs/images in the browser; Word files download.
        $inline = str_starts_with((string) $document->mime_type, 'image/') || $document->mime_type === 'application/pdf';

        return $inline
            ? $disk->response($document->file_path, $document->original_name)
            : $disk->download($document->file_path, $document->original_name);
    }

    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);

        Storage::disk(EmployeeDocument::DISK)->delete($document->file_path);
        $document->delete();

        return back()->with('success', $document->label . ' removed.');
    }

    private function isAdmin(): bool
    {
        $role = str_replace(' ', '_', strtolower(trim((string) (auth()->user()->role ?? 'employee'))));
        return in_array($role, self::ADMIN_ROLES, true);
    }
}
