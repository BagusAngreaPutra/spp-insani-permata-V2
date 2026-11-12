<?php

namespace Tests\Unit;

use App\Models\KoperasiPenjualan;
use App\Models\Tagihan;
use App\Services\WalletBillPaymentService;
use App\Services\WalletCooperativePaymentService;
use App\Services\WalletLedgerService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\CreatesApplication;

class WalletPaymentsIntegrationTest extends TestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sekolah')->nullable();
        });
        Schema::create('receipt_settings', function (Blueprint $table) {
            $table->id();
            $table->string('numbering_period')->default('yearly');
            $table->boolean('show_officer')->default(false);
            $table->timestamps();
        });
        Schema::create('receipt_sequences', function (Blueprint $table) {
            $table->string('scope')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sekolah')->nullable();
        });
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('siswa_id')->unique(); $table->unsignedBigInteger('sekolah_id')->nullable();
            $table->char('currency', 3)->default('IDR'); $table->string('status')->default('active');
            $table->decimal('available_balance', 15, 2)->default(0); $table->decimal('held_balance', 15, 2)->default(0);
            $table->decimal('daily_topup_limit', 15, 2)->nullable(); $table->decimal('daily_payment_limit', 15, 2)->nullable(); $table->timestamps();
        });
        Schema::create('wallet_journals', function (Blueprint $table) {
            $table->id(); $table->string('reference')->unique(); $table->string('type'); $table->string('status')->default('pending');
            $table->unsignedBigInteger('siswa_id')->nullable(); $table->decimal('amount', 15, 2); $table->string('idempotency_key')->nullable()->unique();
            $table->text('description')->nullable(); $table->json('metadata')->nullable(); $table->timestamp('posted_at')->nullable(); $table->timestamp('reversed_at')->nullable(); $table->timestamps();
        });
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('wallet_journal_id'); $table->unsignedBigInteger('wallet_account_id')->nullable();
            $table->string('account_code'); $table->string('direction'); $table->decimal('amount', 15, 2); $table->decimal('balance_after', 15, 2)->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
        });
        Schema::create('wallet_payments', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('wallet_account_id'); $table->unsignedBigInteger('wallet_journal_id')->nullable();
            $table->string('source_type'); $table->unsignedBigInteger('source_id'); $table->decimal('amount', 15, 2); $table->string('status')->default('pending');
            $table->string('idempotency_key')->unique(); $table->timestamp('paid_at')->nullable(); $table->timestamp('reversed_at')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
        });
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('siswa_id'); $table->unsignedBigInteger('id_sekolah')->nullable(); $table->decimal('nominal', 12, 2); $table->string('nama_tagihan');
            $table->string('status')->default('belum'); $table->boolean('is_active')->default(true); $table->string('periode')->nullable(); $table->unsignedBigInteger('tahun_ajaran_id')->nullable(); $table->timestamps();
        });
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('sekolah_id')->nullable(); $table->unsignedBigInteger('tagihan_id'); $table->unsignedBigInteger('siswa_id');
            $table->decimal('jumlah_bayar', 15, 2); $table->decimal('diskon', 15, 2)->default(0); $table->date('tanggal_bayar'); $table->date('tanggal_kwitansi')->nullable();
            $table->string('periode')->nullable(); $table->string('periode_tahun')->nullable(); $table->string('metode_bayar')->default('tunai'); $table->string('transaction_id')->nullable();
            $table->string('nomor_kwitansi')->nullable(); $table->text('keterangan')->nullable(); $table->unsignedBigInteger('posted_by')->nullable(); $table->string('posted_by_name')->nullable(); $table->timestamp('deleted_at')->nullable(); $table->timestamps();
        });
        Schema::create('koperasi_penjualan', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('siswa_id'); $table->unsignedBigInteger('sekolah_id')->nullable(); $table->string('kode_transaksi');
            $table->decimal('total', 12, 2); $table->string('metode_bayar')->nullable(); $table->unsignedBigInteger('posted_by')->nullable(); $table->string('posted_by_name')->nullable(); $table->timestamps();
        });
        DB::table('sekolah')->insert(['id' => 1, 'kode_sekolah' => 'TEST']);
        DB::table('siswa')->insert(['id' => 1, 'id_sekolah' => 1]);
    }

    protected function tearDown(): void
    {
        foreach (['koperasi_penjualan', 'pembayaran', 'tagihan', 'wallet_payments', 'wallet_ledger_entries', 'wallet_journals', 'wallet_accounts', 'receipt_sequences', 'receipt_settings', 'siswa', 'sekolah'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_bill_payment_updates_bill_payment_history_and_wallet_once(): void
    {
        $account = app(WalletLedgerService::class)->getOrCreateAccount(1, 1);
        app(WalletLedgerService::class)->credit($account, 150000, 'topup', 'gateway_clearing', 'test-topup-bill');
        $bill = Tagihan::create(['siswa_id' => 1, 'id_sekolah' => 1, 'nominal' => 100000, 'nama_tagihan' => 'SPP Test', 'status' => 'belum', 'is_active' => true]);

        $service = app(WalletBillPaymentService::class);
        $first = $service->pay($account, [$bill->id], 'test-bill-payment');
        $second = $service->pay($account, [$bill->id], 'test-bill-payment');

        $this->assertCount(1, $first);
        $this->assertCount(1, $second);
        $this->assertSame('lunas', $bill->fresh()->status);
        $this->assertSame('dompet', DB::table('pembayaran')->value('metode_bayar'));
        $this->assertSame('50000.00', (string) $account->fresh()->available_balance);
        $this->assertSame(1, DB::table('pembayaran')->count());
    }

    public function test_cooperative_payment_debits_wallet_and_records_source(): void
    {
        $account = app(WalletLedgerService::class)->getOrCreateAccount(1, 1);
        app(WalletLedgerService::class)->credit($account, 75000, 'topup', 'gateway_clearing', 'test-topup-coop');
        $sale = KoperasiPenjualan::create(['siswa_id' => 1, 'sekolah_id' => 1, 'kode_transaksi' => 'KOP-TEST-1', 'total' => 25000, 'metode_bayar' => 'dompet']);

        $payment = app(WalletCooperativePaymentService::class)->pay($sale, 'test-coop-payment');

        $this->assertSame('koperasi_penjualan', $payment->source_type);
        $this->assertSame('50000.00', (string) $account->fresh()->available_balance);
        $this->assertSame(25000.0, (float) $payment->amount);
    }
}
