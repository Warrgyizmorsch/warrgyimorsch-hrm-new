<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLetter;
use App\Models\LetterTemplate;
use App\Services\LetterGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeLetterController extends Controller
{
    private const ADMIN_ROLES = ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head'];

    public function store(Request $request, $employeeId)
    {
        $request->validate([
            'letter_template_id' => 'required|exists:letter_templates,id',
        ]);

        $employee = Employee::findOrFail($employeeId);
        $template = LetterTemplate::findOrFail($request->letter_template_id);

        $renderedContent = LetterGenerationService::render($template->content, $employee);
        $renderedTitle = LetterGenerationService::render($template->title, $employee);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('letters.letter_pdf', [
            'title' => $renderedTitle,
            'content' => $renderedContent,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
                'isFontSubsettingEnabled' => true,
            ]);

        $filename = 'letters/' . $employee->id . '/' . $template->type . '_' . now()->format('Ymd_His') . '.pdf';
        Storage::disk(EmployeeLetter::DISK)->put($filename, $pdf->output());

        EmployeeLetter::create([
            'employee_id' => $employee->id,
            'letter_template_id' => $template->id,
            'type' => $template->type,
            'title' => $renderedTitle,
            'file_path' => $filename,
            'generated_by' => auth()->id(),
        ]);

        return back()->with('success', $renderedTitle . ' generated successfully.');
    }

    /**
     * View/download a generated letter. Admin roles can open any employee's letters;
     * everyone else only their own.
     */
    public function download($id)
    {
        $letter = EmployeeLetter::findOrFail($id);
        $user = auth()->user();

        if (!$this->isAdmin() && (int) $user->employee_id !== (int) $letter->employee_id) {
            abort(403);
        }

        $disk = Storage::disk(EmployeeLetter::DISK);
        if (!$disk->exists($letter->file_path)) {
            abort(404, 'File not found.');
        }

        return $disk->response($letter->file_path, $letter->title . '.pdf');
    }

    public function destroy($id)
    {
        $letter = EmployeeLetter::findOrFail($id);

        Storage::disk(EmployeeLetter::DISK)->delete($letter->file_path);
        $letter->delete();

        return back()->with('success', 'Letter removed.');
    }

    private function isAdmin(): bool
    {
        $role = str_replace(' ', '_', strtolower(trim((string) (auth()->user()->role ?? 'employee'))));
        return in_array($role, self::ADMIN_ROLES, true);
    }
}
