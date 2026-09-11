<?php

namespace Tests\Feature\Accounts;

use App\Actions\Accounts\PostCurrencyConversion;
use App\Actions\Accounts\PostJournalEntry;
use App\Actions\Accounts\PostManualTransaction;
use App\Actions\Accounts\PostOpeningBalance;
use App\Actions\Accounts\PostTransferWithFee;
use App\Actions\Accounts\ReverseJournalEntry;
use App\Actions\Accounts\VoidJournalEntry;
use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Exceptions\Accounts\DuplicateJournalEntryException;
use App\Exceptions\Accounts\JournalEntryImmutableException;
use App\Exceptions\Accounts\UnbalancedJournalEntryException;
use App\Models\Account;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the M1 ledger primitive against the exact Dr/Cr figures from
 * docs/ecomX-accounts-cases.md, Cases 1.1-1.7 and 9.1, plus the Golden
 * Rule invariants (balance, no duplicate posting) PostJournalEntry enforces.
 */
class PostJournalEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CurrencySeeder::class);
        $this->seed(AccountSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    protected function account(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    public function test_case_1_1_manual_income(): void
    {
        $entry = app(PostManualTransaction::class)->handle(
            $this->account('1010')->id,
            $this->account('4100')->id,
            10000,
            '2026-09-10',
            TransactionType::MANUAL_INCOME,
        );

        $this->assertSame(10000.0, $entry->totalDebit());
        $this->assertSame(10000.0, $entry->totalCredit());
        $this->assertSame(10000.0, $this->account('1010')->balance());
    }

    public function test_case_1_4_transfer_with_fee(): void
    {
        $entry = app(PostTransferWithFee::class)->handle(
            $this->account('1030')->id, // from bKash
            $this->account('1020')->id, // to bank
            2000,
            '2026-09-10',
            $this->account('5300')->id,
            20,
        );

        $this->assertSame(2020.0, $entry->totalDebit());
        $this->assertSame(2020.0, $entry->totalCredit());
        $this->assertSame(2000.0, $this->account('1020')->balance());
        $this->assertSame(20.0, $this->account('5300')->balance());
        $this->assertSame(-2020.0, $this->account('1030')->balance());
    }

    public function test_case_1_5_currency_conversion(): void
    {
        app(PostCurrencyConversion::class)->handle(
            $this->account('1030')->id,
            $this->account('1040')->id,
            12200,
            12000,
            '2026-09-10',
            $this->account('5320')->id,
            200,
        );

        $this->assertSame(12000.0, $this->account('1040')->balance());
        $this->assertSame(200.0, $this->account('5320')->balance());
        $this->assertSame(-12200.0, $this->account('1030')->balance());
    }

    public function test_case_1_6_reverse_and_repost(): void
    {
        $original = app(PostManualTransaction::class)->handle(
            $this->account('5110')->id,
            $this->account('1030')->id,
            5000,
            '2026-09-10',
            TransactionType::MANUAL_EXPENSE,
        );

        $reversal = app(ReverseJournalEntry::class)->handle($original);

        $this->assertSame($original->id, $reversal->reversed_journal_entry_id);
        $this->assertSame(0.0, $this->account('1030')->balance());

        app(PostManualTransaction::class)->handle(
            $this->account('5110')->id,
            $this->account('1030')->id,
            7000,
            '2026-09-10',
            TransactionType::MANUAL_EXPENSE,
        );

        $this->assertSame(-7000.0, $this->account('1030')->balance());
    }

    public function test_case_1_7_void_excludes_entry_from_balance(): void
    {
        $entry = app(PostManualTransaction::class)->handle(
            $this->account('1010')->id,
            $this->account('4100')->id,
            10000,
            '2026-09-10',
            TransactionType::MANUAL_INCOME,
        );

        $voided = app(VoidJournalEntry::class)->handle($entry);

        $this->assertSame(JournalEntryStatus::VOID, $voided->status);
        $this->assertSame(0.0, $this->account('1010')->balance());
    }

    public function test_voiding_an_already_void_entry_throws(): void
    {
        $entry = app(PostManualTransaction::class)->handle(
            $this->account('1010')->id, $this->account('4100')->id, 100, '2026-09-10', TransactionType::MANUAL_INCOME,
        );
        $voided = app(VoidJournalEntry::class)->handle($entry);

        $this->expectException(JournalEntryImmutableException::class);
        app(VoidJournalEntry::class)->handle($voided);
    }

    public function test_case_9_1_opening_balance_balances_via_equity_plug(): void
    {
        $entry = app(PostOpeningBalance::class)->handle([
            $this->account('1010')->id => 50000,
            $this->account('1200')->id => 500000,
            $this->account('1100')->id => 80000,
            $this->account('2100')->id => -120000,
            $this->account('2200')->id => -200000,
        ], $this->account('3950')->id, '2026-09-01');

        $this->assertSame(310000.0, $this->account('3950')->balance());
        $this->assertEqualsWithDelta($entry->totalDebit(), $entry->totalCredit(), 0.001);
    }

    public function test_unbalanced_lines_are_rejected(): void
    {
        $this->expectException(UnbalancedJournalEntryException::class);

        app(PostJournalEntry::class)->handle(
            ['entry_date' => '2026-09-10', 'transaction_type' => TransactionType::MANUAL_EXPENSE->value],
            [
                ['account_id' => $this->account('1010')->id, 'debit' => 100],
                ['account_id' => $this->account('4100')->id, 'credit' => 999],
            ]
        );
    }

    public function test_duplicate_source_purpose_is_rejected(): void
    {
        $header = [
            'entry_date' => '2026-09-10',
            'transaction_type' => TransactionType::MANUAL_INCOME->value,
            'source_type' => 'TestSource',
            'source_id' => 999,
            'purpose' => 'test',
        ];
        $lines = [
            ['account_id' => $this->account('1010')->id, 'debit' => 10],
            ['account_id' => $this->account('4100')->id, 'credit' => 10],
        ];

        app(PostJournalEntry::class)->handle($header, $lines);

        $this->expectException(DuplicateJournalEntryException::class);
        app(PostJournalEntry::class)->handle($header, $lines);
    }
}
