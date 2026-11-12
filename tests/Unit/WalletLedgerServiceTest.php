<?php

namespace Tests\Unit;

use App\Services\WalletLedgerService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\CreatesApplication;

class WalletLedgerServiceTest extends TestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sekolah', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_sekolah')->nullable();
        });
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->unique();
            $table->foreignId('sekolah_id')->nullable();
            $table->char('currency', 3)->default('IDR');
            $table->string('status', 20)->default('active');
            $table->decimal('available_balance', 15, 2)->default(0);
            $table->decimal('held_balance', 15, 2)->default(0);
            $table->decimal('daily_topup_limit', 15, 2)->nullable();
            $table->decimal('daily_payment_limit', 15, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('wallet_journals', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_journal_id');
            $table->unsignedBigInteger('wallet_account_id')->nullable();
            $table->string('account_code');
            $table->string('direction');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('wallet_holds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_account_id');
            $table->unsignedBigInteger('wallet_journal_id')->nullable();
            $table->unsignedBigInteger('capture_journal_id')->nullable();
            $table->unsignedBigInteger('release_journal_id')->nullable();
            $table->string('hold_reference')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('held');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
        DB::table('siswa')->insert(['id' => 1, 'id_sekolah' => null]);
    }

    protected function tearDown(): void
    {
        foreach (['wallet_holds', 'wallet_ledger_entries', 'wallet_journals', 'wallet_accounts', 'siswa', 'sekolah'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_credit_and_debit_are_idempotent_and_prevent_negative_balance(): void
    {
        $service = app(WalletLedgerService::class);
        $account = $service->getOrCreateAccount(1);

        $first = $service->credit($account, '100000.00', 'topup', 'gateway_clearing', 'topup-1');
        $second = $service->credit($account, '100000.00', 'topup', 'gateway_clearing', 'topup-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('100000.00', (string) $account->fresh()->available_balance);

        $service->debit($account, 30000, 'payment', 'school_revenue', 'payment-1');
        $this->assertSame('70000.00', (string) $account->fresh()->available_balance);

        $this->expectException(RuntimeException::class);
        $service->debit($account, 70001, 'payment', 'school_revenue', 'payment-2');
    }

    public function test_hold_capture_and_release_keep_available_and_held_balances_consistent(): void
    {
        $service = app(WalletLedgerService::class);
        $account = $service->getOrCreateAccount(1);
        $service->credit($account, 100000, 'topup', 'gateway_clearing', 'topup-2');

        $hold = $service->hold($account, 25000, 'hold-1');
        $this->assertSame('75000.00', (string) $account->fresh()->available_balance);
        $this->assertSame('25000.00', (string) $account->fresh()->held_balance);

        $service->captureHold($hold, 'school_revenue', 'capture-1');
        $this->assertSame('75000.00', (string) $account->fresh()->available_balance);
        $this->assertSame('0.00', (string) $account->fresh()->held_balance);

        $holdTwo = $service->hold($account, 10000, 'hold-2');
        $service->releaseHold($holdTwo, 'release-1');
        $this->assertSame('75000.00', (string) $account->fresh()->available_balance);
        $this->assertSame('0.00', (string) $account->fresh()->held_balance);
    }
}
