<?php

namespace Tests\Feature;

use App\Http\Controllers\Laporan\LaporanTransaksiController;
use App\Services\BillingWorkspace;
use App\Support\ServerTable;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServerPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // These fixtures must never be created in the application's real database.
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00', 'Asia/Jakarta'));
        Schema::create('sekolah', function (Blueprint $t) { $t->id(); $t->string('nama_sekolah'); });
        Schema::create('kelas', function (Blueprint $t) { $t->id(); $t->integer('sekolah_id')->nullable(); $t->string('tingkat')->nullable(); $t->string('nama_kelas')->nullable(); });
        Schema::create('tahun_ajaran', function (Blueprint $t) { $t->id(); $t->string('nama_tahun'); $t->boolean('aktif')->default(false); });
        Schema::create('jenis_pembayaran', function (Blueprint $t) { $t->id(); $t->integer('tahun_ajaran_id')->nullable(); $t->string('tipe'); $t->date('jatuh_tempo')->nullable(); });
        Schema::create('siswa', function (Blueprint $t) { $t->id(); $t->string('nama'); $t->string('nis'); $t->integer('id_sekolah')->nullable(); $t->integer('kelas_id')->nullable(); });
        Schema::create('tagihan', function (Blueprint $t) {
            $t->id(); $t->integer('siswa_id'); $t->integer('jenis_pembayaran_id')->nullable(); $t->integer('tahun_ajaran_id')->nullable();
            $t->boolean('is_active')->default(true); $t->string('periode')->nullable(); $t->string('nama_tagihan')->nullable(); $t->string('tipe')->nullable();
            $t->decimal('nominal', 15, 2); $t->date('tanggal_jatuh_tempo')->nullable();
        });
        Schema::create('pembayaran', function (Blueprint $t) {
            $t->id(); $t->integer('siswa_id')->nullable(); $t->integer('tagihan_id')->nullable(); $t->string('transaction_id')->nullable(); $t->string('nomor_kwitansi')->nullable();
            $t->decimal('jumlah_bayar', 15, 2); $t->decimal('diskon', 15, 2)->default(0); $t->date('tanggal_bayar'); $t->string('periode')->nullable();
            $t->string('metode_bayar')->nullable(); $t->text('keterangan')->nullable(); $t->softDeletes();
        });
        Schema::create('koperasi_penjualan', function (Blueprint $t) { $t->id(); $t->integer('siswa_id')->nullable(); $t->integer('sekolah_id')->nullable(); $t->date('tanggal'); $t->decimal('total', 15, 2); $t->string('kode_transaksi')->nullable(); $t->text('catatan')->nullable(); $t->string('metode_bayar')->nullable(); $t->softDeletes(); });
        Schema::create('koperasi_penjualan_detail', function (Blueprint $t) { $t->id(); $t->integer('koperasi_penjualan_id'); $t->string('nama_barang'); $t->integer('jumlah'); });
        Schema::create('pemasukan', function (Blueprint $t) { $t->id(); $t->integer('sekolah_id')->nullable(); $t->date('tanggal'); $t->decimal('jumlah', 15, 2); $t->string('sumber')->nullable(); $t->text('keterangan')->nullable(); });
        DB::table('sekolah')->insert(['id' => 1, 'nama_sekolah' => 'Sekolah Uji']);
        DB::table('tahun_ajaran')->insert([['id'=>1,'nama_tahun'=>'2026/2027','aktif'=>true],['id'=>2,'nama_tahun'=>'2025/2026','aktif'=>false]]);
        for ($id = 1; $id <= 42; $id++) DB::table('siswa')->insert(['id'=>$id,'nama'=>'Siswa '.$id,'nis'=>'N'.$id,'id_sekolah'=>1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_database_page_limits_and_print_mode_are_explicit(): void
    {
        DB::enableQueryLog();
        $query = DB::table('siswa')->orderBy('id');
        $page = ServerTable::paginate($query, Request::create('/siswa', 'GET', ['per_page'=>10,'page'=>3]));
        $this->assertSame(42, $page->total());
        $this->assertCount(10, $page->items());
        $this->assertSame(21, $page->firstItem());
        $this->assertStringContainsString('limit 10 offset 20', collect(DB::getQueryLog())->last()['query']);
        $bounded = ServerTable::paginate($query, Request::create('/siswa', 'GET', ['per_page'=>999999,'page'=>999999]));
        $this->assertSame(25, $bounded->perPage());
        $this->assertSame(2, $bounded->currentPage());
        $this->assertCount(17, $bounded->items());
        $print = ServerTable::paginate($query, Request::create('/siswa', 'GET', ['print'=>1,'page'=>3,'per_page'=>10]));
        $this->assertCount(42, $print->items());
        $this->assertSame(1, $print->currentPage());
    }

    public function test_billing_summary_keeps_legacy_period_due_date_and_discount_rules(): void
    {
        DB::table('jenis_pembayaran')->insert([['id'=>1,'tahun_ajaran_id'=>1,'tipe'=>'sekali'],['id'=>2,'tahun_ajaran_id'=>1,'tipe'=>'bulanan']]);
        $bill = fn ($id, $student, $amount, $extra = []) => array_merge(['id'=>$id,'siswa_id'=>$student,'nominal'=>$amount,'tahun_ajaran_id'=>1,'tanggal_jatuh_tempo'=>'2026-10-01'], $extra);
        foreach ([
            $bill(1,1,1000), $bill(2,1,500,['tanggal_jatuh_tempo'=>'2026-11-01']), $bill(3,1,9999,['is_active'=>false]),
            $bill(4,2,200,['tanggal_jatuh_tempo'=>'2026-11-01']), $bill(5,3,100),
            $bill(6,4,9999,['jenis_pembayaran_id'=>1,'tahun_ajaran_id'=>2,'periode'=>'2025-07']),
            $bill(7,5,50,['jenis_pembayaran_id'=>1,'periode'=>'2026-07']),
            $bill(8,1,9999,['jenis_pembayaran_id'=>2,'periode'=>'2028-01']),
        ] as $row) DB::table('tagihan')->insert($row);
        DB::table('pembayaran')->insert([
            ['id'=>1,'tagihan_id'=>1,'jumlah_bayar'=>100,'diskon'=>50,'tanggal_bayar'=>'2026-10-01','deleted_at'=>null],
            ['id'=>2,'tagihan_id'=>1,'jumlah_bayar'=>500,'diskon'=>10,'tanggal_bayar'=>'2026-10-01','deleted_at'=>'2026-10-02'],
            ['id'=>3,'tagihan_id'=>5,'jumlah_bayar'=>80,'diskon'=>20,'tanggal_bayar'=>'2026-10-01','deleted_at'=>null],
        ]);
        $service = app(BillingWorkspace::class);
        $query = $service->query(1)->where('siswa.id','<=',5);
        $rows = $query->get()->keyBy('id');
        $this->assertEquals(850, $rows[1]->sisa_bayar);
        $this->assertEquals(1350, $rows[1]->sisa_aktual);
        $this->assertSame('sebagian', $rows[1]->workspace_status);
        $this->assertSame('belum_jatuh_tempo', $rows[2]->workspace_status);
        $this->assertSame('lunas', $rows[3]->workspace_status);
        $this->assertSame('kosong', $rows[4]->workspace_status);
        $this->assertEquals(50, $rows[5]->total_nominal);
        $summary = $service->summary($query);
        $this->assertEquals(5, $summary['total_siswa']);
        $this->assertEquals(1850, $summary['total_nominal']);
        $this->assertEquals(180, $summary['total_dibayar']);
        $this->assertEquals(70, $summary['total_diskon']);
        $this->assertEquals(900, $summary['sisa_bayar']);
        $this->assertEquals(1600, $summary['sisa_aktual']);
        $this->assertEquals(1, $summary['siswa_lunas']);
        $this->assertEquals(1, $service->summary($service->query(1)->where('siswa.id',1))['total_siswa']);
    }

    public function test_grouped_report_keeps_transactions_whole_and_totals_global(): void
    {
        foreach ([
            ['id'=>1,'transaction_id'=>'G1','jumlah_bayar'=>10,'diskon'=>2,'tanggal_bayar'=>'2026-01-01','metode_bayar'=>'tunai'],
            ['id'=>2,'transaction_id'=>'G1','jumlah_bayar'=>20,'diskon'=>3,'tanggal_bayar'=>'2026-06-01','metode_bayar'=>'kjc'],
            ['id'=>3,'transaction_id'=>'G2','jumlah_bayar'=>40,'diskon'=>0,'tanggal_bayar'=>'2026-02-01','metode_bayar'=>'transfer'],
            ['id'=>4,'transaction_id'=>'deleted','jumlah_bayar'=>999,'diskon'=>0,'tanggal_bayar'=>'2026-01-01','deleted_at'=>'2026-01-02'],
        ] as $row) DB::table('pembayaran')->insert(array_merge(['siswa_id'=>1],$row));
        DB::table('koperasi_penjualan')->insert(['id'=>1,'siswa_id'=>1,'sekolah_id'=>1,'tanggal'=>'2026-03-01','total'=>200,'metode_bayar'=>'tunai']);
        for ($id = 1; $id <= 11; $id++) DB::table('pemasukan')->insert(['id'=>$id,'sekolah_id'=>1,'tanggal'=>sprintf('2026-04-%02d',$id),'jumlah'=>1,'sumber'=>'Donasi']);
        $controller = app(LaporanTransaksiController::class);
        $data = $controller->index(Request::create('/laporan/transaksi', 'GET', ['per_page'=>10]))->getData();
        $this->assertSame(14, $data['transactions']->total());
        $this->assertCount(10, $data['transactions']);
        $this->assertEquals(286, $data['summary']['gross']);
        $this->assertEquals(281, $data['summary']['received']);
        $this->assertEquals(5, $data['summary']['discount']);
        $this->assertEquals(210, $data['summary']['funds']['tunai']);
        $this->assertEquals(11, $data['summary']['funds']['belum']);
        $second = $controller->index(Request::create('/laporan/transaksi', 'GET', ['per_page'=>10,'page'=>2]))->getData();
        $group = $second['transactions']->getCollection()->firstWhere('reference', 'G1');
        $this->assertSame(2, $group['item_count']);
        $this->assertSame('2026-01-01', $group['date']->toDateString());
        $this->assertEquals($data['summary'], $second['summary']);
        $print = $controller->index(Request::create('/laporan/transaksi', 'GET', ['print'=>1,'per_page'=>10,'page'=>2]))->getData();
        $this->assertCount(14, $print['transactions']);
        $this->assertEquals($data['summary'], $print['summary']);
        $search = $controller->index(Request::create('/laporan/transaksi', 'GET', ['search'=>'G1']))->getData();
        $this->assertSame(1, $search['transactions']->total());
        $this->assertEquals(30, $search['summary']['received']);
    }
}
