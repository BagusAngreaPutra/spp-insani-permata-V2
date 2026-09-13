<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\JenisPembayaran;
use App\Models\SppDiscountSetting;
use App\Models\Tagihan;
use App\Services\SppDiscountCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SppDiscountSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_customize_spp_discount_amount_and_period(): void
    {
        $admin = Admin::create([
            'nama_admin' => 'Administrator Diskon',
            'username' => 'admin-diskon',
            'password' => 'password',
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin, 'web')
            ->get(route('pengaturan.diskon-spp.edit'))
            ->assertOk()
            ->assertSee('Diskon SPP');

        $this->actingAs($admin, 'web')
            ->put(route('pengaturan.diskon-spp.update'), [
                'nominal' => 'Rp30.000',
                'berlaku_mulai' => '2026-09-01',
                'berlaku_sampai' => '2026-09-30',
                'aktif' => '1',
            ])
            ->assertRedirect(route('pengaturan.diskon-spp.edit'))
            ->assertSessionHas('success');

        $setting = SppDiscountSetting::query()->firstOrFail();
        $this->assertSame(30000.0, (float) $setting->nominal);
        $this->assertSame('2026-09-01', $setting->berlaku_mulai->toDateString());
        $this->assertSame('2026-09-30', $setting->berlaku_sampai->toDateString());

        $bill = new Tagihan([
            'nama_tagihan' => 'SPP September 2026',
            'nominal' => 175000,
            'periode' => '2026-09',
            'tanggal_jatuh_tempo' => '2026-09-10',
        ]);
        $bill->setRelation('jenisPembayaran', new JenisPembayaran(['nama_pembayaran' => 'SPP']));
        $calculator = app(SppDiscountCalculator::class);

        $this->assertSame(30000.0, $calculator->settlementDiscount($bill, '2026-09-20', 145000));
        $this->assertSame(0.0, $calculator->settlementDiscount($bill, '2026-10-01', 145000));
    }
}
