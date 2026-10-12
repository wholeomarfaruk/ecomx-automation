<?php

namespace App\Actions\Profiles;

use App\Models\MasterProfile;
use App\Services\Profiles\ProfileBalances;
use App\Sms\SmsManager;
use App\Support\Bdt;
use App\Support\BdPhone;
use Throwable;

/**
 * Due reminders and balance statements by SMS, through the app's SMS
 * gateway manager (whichever gateway is active; it queues when configured
 * to). Every send — or failure — is written to the profile's timeline.
 */
class SendProfileSms
{
    public function __construct(protected SmsManager $sms, protected ProfileBalances $balances) {}

    public function dueReminder(MasterProfile $profile): bool
    {
        $due = (float) $this->balances->forProfile($profile->id)->receivable;

        if ($due <= 0.5) {
            return false;
        }

        $site = \App\Models\Setting::get('site_name', config('app.name'), 'general') ?: config('app.name');
        $text = "প্রিয় {$profile->display_name}, {$site}-এ আপনার বাকি " . Bdt::money($due) . '। অনুগ্রহ করে দ্রুত পরিশোধ করুন। ধন্যবাদ।';

        return $this->send($profile, $text, 'Due reminder SMS sent');
    }

    public function statement(MasterProfile $profile, float $closing): bool
    {
        $site = \App\Models\Setting::get('site_name', config('app.name'), 'general') ?: config('app.name');
        $side = $closing > 0 ? 'আপনার কাছে পাওনা' : 'আপনাকে দেওয়া বাকি';
        $text = abs($closing) < 0.5
            ? "প্রিয় {$profile->display_name}, {$site}-এর সাথে আপনার হিসাব এখন Clear। ধন্যবাদ।"
            : "প্রিয় {$profile->display_name}, " . now()->format('d M Y') . " পর্যন্ত {$site}-এর হিসাবে {$side} " . Bdt::money(abs($closing)) . '।';

        return $this->send($profile, $text, 'Statement sent by SMS');
    }

    public function send(MasterProfile $profile, string $text, string $activity): bool
    {
        $to = BdPhone::normalize($profile->phone);

        if (! $to) {
            return false;
        }

        try {
            $response = $this->sms->send($to, $text, 'accounts');
            $ok = $response->success ?? true;
        } catch (Throwable $e) {
            report($e);
            $ok = false;
        }

        $profile->logActivity('sms', $ok ? $activity : "{$activity} — failed", ['to' => $to]);

        return $ok;
    }
}
