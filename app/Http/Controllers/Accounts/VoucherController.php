<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\VoucherAttachment;
use App\Services\Accounts\Vouchers;
use App\Support\AccountsAccess;
use App\Support\AccountsSettings;
use Illuminate\Support\Facades\Storage;

/** Voucher attachments (private disk) and the printable voucher. */
class VoucherController extends Controller
{
    public function attachment(VoucherAttachment $attachment)
    {
        AccountsAccess::authorize('view');
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->response($attachment->path, $attachment->name);
    }

    public function print(JournalEntry $entry)
    {
        AccountsAccess::authorize('view');
        $entry->load(['lines.account', 'createdBy:id,name', 'postedBy:id,name', 'approvedBy:id,name']);
        $v = app(Vouchers::class);

        return view('accounts.voucher-print', [
            'e'        => $entry,
            'type'     => $v->typeLabel($entry),
            'party'    => $v->partyOf($entry),
            'vouchers' => $v,
            'business' => AccountsSettings::businessName(),
            'bin'      => AccountsSettings::get('bin'),
        ]);
    }
}
