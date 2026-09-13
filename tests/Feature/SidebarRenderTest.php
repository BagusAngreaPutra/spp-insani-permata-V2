<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminPermission;
use Tests\TestCase;

class SidebarRenderTest extends TestCase
{
    public function test_super_admin_can_render_current_sidebar_groups(): void
    {
        $this->actingAs(new Admin(['role' => 'super_admin']), 'web');

        $this->view('layouts.sidebar')
            ->assertSee('<span>Pemasukan</span>', false)
            ->assertSee('<span>Data sekolah</span>', false)
            ->assertSee('<span>Riwayat Transaksi</span>', false)
            ->assertSee('<span>Laporan Transaksi</span>', false)
            ->assertSee('<span>Pengaturan data</span>', false)
            ->assertDontSee('<span>Ringkasan</span>', false)
            ->assertDontSee('<span>Keuangan</span>', false);
    }

    public function test_menu_groups_follow_each_permission(): void
    {
        $cases = [
            'tagihan.manage' => ['Pemasukan', 'Pengaturan data'],
            'pembayaran.process' => ['Pemasukan'],
            'koperasi.penjualan.manage' => ['Pemasukan'],
            'pemasukan.manage' => ['Pemasukan'],
            'jenis_pembayaran.manage' => ['Data sekolah'],
            'riwayat.view' => ['Riwayat Transaksi'],
            'pengeluaran.manage' => [],
            'keuangan_kas.view' => [],
            '' => [],
        ];

        foreach ($cases as $permission => $expectedItems) {
            $admin = new Admin(['role' => 'admin']);
            $admin->setRelation('permissions', collect(
                $permission ? [new AdminPermission(['permission' => $permission])] : []
            ));
            $this->actingAs($admin, 'web');

            $view = $this->view('layouts.sidebar');
            foreach (['Pemasukan', 'Data sekolah', 'Riwayat Transaksi', 'Pengaturan data'] as $item) {
                if (in_array($item, $expectedItems, true)) {
                    $view->assertSee('<span>'.$item.'</span>', false);
                } else {
                    $view->assertDontSee('<span>'.$item.'</span>', false);
                }
            }

            $view->assertDontSee('<span>Ringkasan</span>', false)
                ->assertDontSee('<span>Keuangan</span>', false);
        }
    }
}
