@extends('layouts.app')
@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">Dompet Digital Siswa</h1>
    <div class="row g-3 mb-4">
        @foreach([['Akun',$stats['accounts']],['Saldo tersedia','Rp'.number_format($stats['balance'],0,',','.')],['Saldo ditahan','Rp'.number_format($stats['held'],0,',','.')],['Top up pending',$stats['pending_topups']],['Pembayaran',$stats['payments']],['Webhook error',$stats['webhook_errors']]] as $card)
            <div class="col-md-4 col-xl-2"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ $card[0] }}</small><div class="h5 mb-0">{{ $card[1] }}</div></div></div></div>
        @endforeach
    </div>
    <form class="mb-3"><input class="form-control" name="search" value="{{ $search }}" placeholder="Cari nama atau NIS siswa"></form>
    <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Siswa</th><th>Sekolah</th><th>Status</th><th>Saldo tersedia</th><th>Ditahan</th></tr></thead><tbody>
        @forelse($accounts as $account)<tr><td>{{ $account->siswa?->nama }}<br><small>{{ $account->siswa?->nis }}</small></td><td>{{ $account->siswa?->sekolah?->nama_sekolah ?? '-' }}</td><td>{{ ucfirst($account->status) }}</td><td>Rp{{ number_format((float)$account->available_balance,0,',','.') }}</td><td>Rp{{ number_format((float)$account->held_balance,0,',','.') }}</td></tr>@empty<tr><td colspan="5">Belum ada akun wallet.</td></tr>@endforelse
    </tbody></table></div><div class="card-body">{{ $accounts->links() }}</div></div>
</div>
@endsection
