<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Holiday;

class HolidayResolverService
{
    public function __construct(private readonly Holiday $holidays = new Holiday())
    {
    }

    public function resolve(int $employeeId, string $workDate): ?array
    {
        $holiday = $this->holidays->findApplicableByDate($workDate);
        if ($holiday === null) {
            return null;
        }

        return [
            'holiday_id' => (int) $holiday['holiday_id'],
            'holiday_name' => (string) ($holiday['holiday_name'] ?? ''),
            'holiday_type' => (string) ($holiday['holiday_type'] ?? ''),
            'holiday_date' => (string) ($holiday['resolved_holiday_date'] ?? $holiday['holiday_date'] ?? $workDate),
            'is_recurring' => !empty($holiday['is_recurring']),
            'paid_holiday' => !empty($holiday['paid_holiday']),
            'salary_multiplier' => isset($holiday['salary_multiplier']) ? (float) $holiday['salary_multiplier'] : null,
            'allowance_amount' => isset($holiday['allowance_amount']) ? (float) $holiday['allowance_amount'] : null,
            'description' => $holiday['description'] ?? null,
        ];
    }
}
