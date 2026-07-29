<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'detail',
        'icon',
        'color',
        'is_read',
        'meta',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'meta'    => 'array',
    ];

    /**
     * Create a "new crisis flagged" notification.
     */
    public static function crisisFlagged(CrisisAlert $alert): self
    {
        return self::create([
            'type'    => 'crisis_flagged',
            'title'   => 'New Flagged Message',
            'message' => 'A student message was flagged and is awaiting severity classification.',
            'detail'  => 'Flag reason: ' . ($alert->flag_reason ?? 'Negative emotional pattern detected')
                       . '. Keywords: ' . implode(', ', $alert->detected_keywords ?? []),
            'icon'    => 'bx bx-error-circle',
            'color'   => 'red',
            'is_read' => false,
            'meta'    => [
                'alert_id'   => $alert->id,
                'flag_reason' => $alert->flag_reason,
            ],
        ]);
    }

    /**
     * Create a "PDF report exported" notification.
     */
    public static function reportExported(string $period, array $sections): self
    {
        $sectionLabels = implode(', ', array_map('ucfirst', $sections));
        return self::create([
            'type'    => 'report_exported',
            'title'   => 'PDF Report Downloaded',
            'message' => "An analytics PDF report was downloaded (period: {$period}).",
            'detail'  => "Sections included: {$sectionLabels}.",
            'icon'    => 'bx bxs-file-pdf',
            'color'   => 'blue',
            'is_read' => false,
            'meta'    => [
                'format'   => 'pdf',
                'period'   => $period,
                'sections' => $sections,
            ],
        ]);
    }

    /**
     * Create a "CSV report exported" notification.
     */
    public static function csvExported(string $period): self
    {
        return self::create([
            'type'    => 'csv_exported',
            'title'   => 'CSV Report Downloaded',
            'message' => "An analytics CSV report was downloaded (period: {$period}).",
            'detail'  => 'Includes dashboard stats, emotion distribution, sentiment trends, peak usage hours, and daily snapshots.',
            'icon'    => 'bx bx-spreadsheet',
            'color'   => 'green',
            'is_read' => false,
            'meta'    => [
                'format' => 'csv',
                'period' => $period,
            ],
        ]);
    }

    /**
     * Create a "log records CSV exported" notification.
     */
    public static function logCsvExported(int $totalRecords, array $filters = []): self
    {
        $filterParts = [];
        if (!empty($filters['department']) && $filters['department'] !== 'All Departments') {
            $filterParts[] = 'Dept: ' . $filters['department'];
        }
        if (!empty($filters['status']) && $filters['status'] !== 'All Sessions') {
            $filterParts[] = 'Status: ' . $filters['status'];
        }
        $filterLabel = !empty($filterParts) ? implode(', ', $filterParts) : 'No filters applied';

        return self::create([
            'type'    => 'log_csv_exported',
            'title'   => 'Log Records CSV Downloaded',
            'message' => "A session log CSV was exported with {$totalRecords} record(s).",
            'detail'  => $filterLabel,
            'icon'    => 'bx bx-spreadsheet',
            'color'   => 'green',
            'is_read' => false,
            'meta'    => [
                'format'        => 'csv',
                'total_records' => $totalRecords,
                'filters'       => $filters,
            ],
        ]);
    }

    /**
     * Create an "urgent help needed" notification.
     */
    public static function urgentHelpNeeded(\App\Models\User $user, int $severeCount): self
    {
        $maskedEmail = \App\Helpers\DataFormatter::maskEmail($user->email);
        $totalCount = CrisisAlert::where('user_id', $user->id)->count();
        return self::create([
            'type'    => 'multiple_severe_alerts',
            'title'   => 'Urgent Wellness Check',
            'message' => "Student ({$maskedEmail}) has {$totalCount} crisis alert(s) — {$severeCount} classified severe.",
            'detail'  => 'This student requires immediate wellness checks and counselor intervention.',
            'icon'    => 'bx bxs-error-circle',
            'color'   => 'red',
            'is_read' => false,
            'meta'    => [
                'user_id'      => $user->id,
                'severe_count' => $severeCount,
                'total_count'  => $totalCount,
            ],
        ]);
    }
}

