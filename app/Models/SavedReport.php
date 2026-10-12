<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavedReport extends Model
{
    public const SCHEDULES = ['daily' => 'প্রতিদিন রাত ৯টায়', 'weekly' => 'প্রতি শনিবার সকাল ১০টায়', 'monthly' => 'প্রতি মাসের ১ তারিখ', 'month_end' => 'মাস শেষে', 'none' => 'Schedule নেই — শুধু save'];

    public const RECIPIENTS = ['owners' => 'Owners', 'accountant' => 'Accountant', 'both' => 'Owners + Accountant', 'me' => 'শুধু আমি'];

    public const CHANNELS = ['archive' => 'PDF archive-এ রাখো', 'email' => 'Email', 'whatsapp' => 'WhatsApp (PDF)', 'sms' => 'SMS-এ ছোট summary'];

    protected $fillable = ['name', 'report_key', 'filters', 'schedule', 'recipients', 'channels', 'last_run_at', 'created_by'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'channels' => 'array', 'last_run_at' => 'datetime'];
    }

    public function archives(): HasMany
    {
        return $this->hasMany(ReportArchive::class);
    }
}
