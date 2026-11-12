<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class ServerTable
{
    public static function size(Request $request): int
    {
        $size = filter_var($request->input('per_page', 25), FILTER_VALIDATE_INT);
        return in_array($size, [10, 25, 50, 100], true) ? $size : 25;
    }

    public static function paginate($query, Request $request): LengthAwarePaginator
    {
        $total = (clone $query)->reorder()->count();
        $size = $request->boolean('print') ? max(1, $total) : self::size($request);
        $requested = filter_var($request->input('page', 1), FILTER_VALIDATE_INT) ?: 1;
        $page = $request->boolean('print') ? 1 : max(1, min($requested, max(1, (int) ceil($total / $size))));
        return (new LengthAwarePaginator(
            (clone $query)->forPage($page, $size)->get(), $total, $size, $page,
            ['path' => $request->url(), 'pageName' => 'page']
        ))->appends($request->except('page'));
    }

    public static function direction(Request $request, string $default = 'asc'): string
    {
        return in_array($request->input('direction'), ['asc', 'desc'], true) ? $request->input('direction') : $default;
    }

    public static function paymentKey(string $table = 'pembayaran'): string
    {
        $sqlite = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite';
        $fallback = $sqlite
            ? "'payment-' || {$table}.id" : "CONCAT('payment-', {$table}.id)";
        $binary = $sqlite ? '' : 'BINARY ';
        return "COALESCE(NULLIF(NULLIF({$binary}{$table}.transaction_id, ''), '0'), NULLIF(NULLIF({$binary}{$table}.nomor_kwitansi, ''), '0'), {$fallback})";
    }
}
