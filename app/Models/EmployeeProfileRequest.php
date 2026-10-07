<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Personal / ID / bank details an employee submitted for HR approval. Used both for the
 * first-time profile (type onboarding) and later edits (type change). The employee record is
 * only updated when HR approves.
 */
class EmployeeProfileRequest extends Model
{
    public const TYPE_ONBOARDING = 'onboarding';
    public const TYPE_CHANGE = 'change';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RETURNED = 'returned';

    /** The only employee fields an employee can fill in themselves (label for the review screen). */
    public const FIELDS = [
        'date_of_birth' => 'Date of Birth',
        'gender' => 'Gender',
        'address' => 'Address',
        'aadhaar_number' => 'Aadhaar No.',
        'pan_number' => 'PAN No.',
        'bank_name' => 'Bank Name',
        'account_number' => 'Account No.',
        'ifsc_code' => 'IFSC Code',
    ];

    /** Document types an employee may upload themselves (offer letter etc. stay HR-only). */
    public const DOCUMENT_TYPES = ['aadhaar', 'pan'];

    protected $fillable = ['employee_id', 'type', 'data', 'status', 'review_note', 'submitted_at', 'reviewed_by', 'reviewed_at'];

    protected $casts = [
        'data' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Validation for the self-service fields. $withConfirmation adds the "type account number
     * twice" check — used on the employee's form, not when HR corrects a value.
     */
    public static function rules(bool $withConfirmation = true): array
    {
        return [
            'date_of_birth' => ['required', 'date', 'before:' . now()->subYears(14)->toDateString(), 'after:1940-01-01'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'address' => ['required', 'string', 'max:500'],
            'aadhaar_number' => ['required', 'regex:/^\d{12}$/'],
            'pan_number' => ['required', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => array_values(array_filter(['required', 'regex:/^\d{9,18}$/', $withConfirmation ? 'confirmed' : null])),
            'ifsc_code' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
        ];
    }

    public static function messages(): array
    {
        return [
            'date_of_birth.before' => 'Please check the date of birth.',
            'aadhaar_number.regex' => 'Aadhaar number must be 12 digits.',
            'pan_number.regex' => 'PAN must look like ABCDE1234F (5 letters, 4 digits, 1 letter).',
            'account_number.regex' => 'Account number must be 9–18 digits.',
            'account_number.confirmed' => 'The two account numbers don\'t match.',
            'ifsc_code.regex' => 'IFSC must be 11 characters: 4 letters, then 0, then 6 letters/digits (e.g. ICIC0006685).',
        ];
    }

    /** Strip spaces/dashes and upper-case IDs before validating, so "5045 8292 6978" is accepted. */
    public static function normalize(array $input): array
    {
        foreach (['aadhaar_number', 'account_number', 'account_number_confirmation'] as $key) {
            if (isset($input[$key])) {
                $input[$key] = preg_replace('/[\s-]+/', '', (string) $input[$key]);
            }
        }
        foreach (['pan_number', 'ifsc_code'] as $key) {
            if (isset($input[$key])) {
                $input[$key] = strtoupper(preg_replace('/\s+/', '', (string) $input[$key]));
            }
        }

        return $input;
    }

    /** Current values on the employee record, for prefilling and the old-vs-new comparison. */
    public static function currentValues(Employee $employee): array
    {
        $values = [];
        foreach (array_keys(self::FIELDS) as $field) {
            $value = $employee->$field;
            $values[$field] = $field === 'date_of_birth' && $value ? Carbon::parse($value)->toDateString() : $value;
        }

        return $values;
    }

    /** Fields whose submitted value differs from what's on the employee record now. */
    public function changedFields(): array
    {
        $current = self::currentValues($this->employee);

        return array_keys(array_filter(self::FIELDS, fn ($label, $field) => (string) ($this->data[$field] ?? '') !== (string) ($current[$field] ?? ''), ARRAY_FILTER_USE_BOTH));
    }
}
