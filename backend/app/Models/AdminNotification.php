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
     * Create a "report exported" notification.
     */
    public static function reportExported(string $period, array $sections): self
    {
        $sectionLabels = implode(', ', array_map('ucfirst', $sections));
        return self::create([
            'type'    => 'report_exported',
            'title'   => 'Analytics Report Exported',
            'message' => "A PDF analytics report was downloaded (period: {$period}).",
            'detail'  => "Sections included: {$sectionLabels}.",
            'icon'    => 'bx bx-download',
            'color'   => 'blue',
            'is_read' => false,
            'meta'    => [
                'period'   => $period,
                'sections' => $sections,
            ],
        ]);
    }
}
