<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\WalletAccount;
use App\Models\WalletHold;
use App\Models\WalletJournal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class WalletLedgerService
{
    public function getOrCreateAccount(Siswa|int $student, ?int $schoolId = null): WalletAccount
    {
        $studentId = $student instanceof Siswa ? $student->getKey() : (int) $student;
        $schoolId ??= $student instanceof Siswa ? $student->id_sekolah : null;

        return DB::transaction(function () use ($studentId, $schoolId): WalletAccount {
            $account = WalletAccount::where('siswa_id', $studentId)->lockForUpdate()->first();

            if ($account) {
                if ($schoolId !== null && $account->sekolah_id !== $schoolId) {
                    $account->update(['sekolah_id' => $schoolId]);
                }

                return $account->fresh();
            }

            return WalletAccount::create([
                'siswa_id' => $studentId,
                'sekolah_id' => $schoolId,
                'currency' => 'IDR',
                'status' => 'active',
                'available_balance' => '0.00',
                'held_balance' => '0.00',
            ]);
        });
    }

    public function credit(
        WalletAccount|int $account,
        int|float|string $amount,
        string $type = 'topup',
        string $counterpartyCode = 'gateway_clearing',
        ?string $idempotencyKey = null,
        array $metadata = [],
        ?string $description = null,
    ): WalletJournal {
        return $this->moveAvailable(
            $account,
            $amount,
            $type,
            'credit',
            $counterpartyCode,
            $idempotencyKey,
            $metadata,
            $description,
        );
    }

    public function debit(
        WalletAccount|int $account,
        int|float|string $amount,
        string $type = 'payment',
        string $counterpartyCode = 'school_revenue',
        ?string $idempotencyKey = null,
        array $metadata = [],
        ?string $description = null,
    ): WalletJournal {
        return $this->moveAvailable(
            $account,
            $amount,
            $type,
            'debit',
            $counterpartyCode,
            $idempotencyKey,
            $metadata,
            $description,
        );
    }

    public function hold(
        WalletAccount|int $account,
        int|float|string $amount,
        string $holdReference,
        array $metadata = [],
        ?\DateTimeInterface $expiresAt = null,
    ): WalletHold {
        $minor = $this->toMinorUnits($amount);
        if ($minor <= 0) {
            throw new InvalidArgumentException('Nominal hold harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($account, $minor, $holdReference, $metadata, $expiresAt): WalletHold {
            $existing = WalletHold::where('hold_reference', $holdReference)->lockForUpdate()->first();
            if ($existing) {
                if ($this->toMinorUnits($existing->amount) !== $minor || (int) $existing->wallet_account_id !== (int) ($account instanceof WalletAccount ? $account->getKey() : $account)) {
                    throw new RuntimeException('Hold reference sudah digunakan untuk transaksi lain.');
                }
                return $existing->load('walletAccount', 'journal');
            }

            $wallet = $this->lockAccount($account);
            $this->ensureActive($wallet);
            $available = $this->toMinorUnits($wallet->available_balance);
            if ($available < $minor) {
                throw new RuntimeException('Saldo wallet tidak mencukupi.');
            }

            $newAvailable = $available - $minor;
            $newHeld = $this->toMinorUnits($wallet->held_balance) + $minor;
            $journal = $this->createJournal(
                $wallet,
                'hold',
                $minor,
                'hold:'.$holdReference,
                $metadata,
                'Menahan saldo wallet',
            );

            $wallet->forceFill([
                'available_balance' => $this->fromMinorUnits($newAvailable),
                'held_balance' => $this->fromMinorUnits($newHeld),
            ])->save();

            $this->createEntries($journal, $wallet, $minor, 'debit', 'wallet_held', 'credit', $newAvailable, $metadata, 'wallet_available');
            $journal->forceFill(['status' => 'posted', 'posted_at' => now()])->save();

            return WalletHold::create([
                'wallet_account_id' => $wallet->getKey(),
                'wallet_journal_id' => $journal->getKey(),
                'hold_reference' => $holdReference,
                'amount' => $this->fromMinorUnits($minor),
                'status' => 'held',
                'expires_at' => $expiresAt,
            ]);
        });
    }

    public function captureHold(
        WalletHold|string $hold,
        string $counterpartyCode = 'school_revenue',
        ?string $idempotencyKey = null,
        array $metadata = [],
    ): WalletJournal {
        return DB::transaction(function () use ($hold, $counterpartyCode, $idempotencyKey, $metadata): WalletJournal {
            $lockedHold = $this->lockHold($hold);
            if ($lockedHold->status === 'captured' && $lockedHold->captureJournal) {
                return $lockedHold->captureJournal;
            }
            if ($lockedHold->status !== 'held') {
                throw new RuntimeException('Hold tidak dapat dicapture karena statusnya '.$lockedHold->status.'.');
            }

            $wallet = WalletAccount::whereKey($lockedHold->wallet_account_id)->lockForUpdate()->firstOrFail();
            $minor = $this->toMinorUnits($lockedHold->amount);
            $held = $this->toMinorUnits($wallet->held_balance);
            if ($held < $minor) {
                throw new RuntimeException('Saldo held wallet tidak konsisten.');
            }

            $journal = $this->findIdempotent($idempotencyKey);
            if (!$journal) {
                $journal = $this->createJournal(
                    $wallet,
                    'capture',
                    $minor,
                    $idempotencyKey,
                    array_merge($metadata, ['hold_reference' => $lockedHold->hold_reference]),
                    'Capture saldo wallet',
                );
                $newHeld = $held - $minor;
                $wallet->forceFill(['held_balance' => $this->fromMinorUnits($newHeld)])->save();
                $this->createEntries($journal, $wallet, $minor, 'debit', $counterpartyCode, 'credit', $this->toMinorUnits($wallet->available_balance), $metadata, 'wallet_held');
                $journal->forceFill(['status' => 'posted', 'posted_at' => now()])->save();
            }

            $lockedHold->forceFill([
                'status' => 'captured',
                'capture_journal_id' => $journal->getKey(),
                'captured_at' => now(),
            ])->save();

            return $journal->fresh('entries');
        });
    }

    public function releaseHold(
        WalletHold|string $hold,
        ?string $idempotencyKey = null,
        array $metadata = [],
    ): WalletJournal {
        return DB::transaction(function () use ($hold, $idempotencyKey, $metadata): WalletJournal {
            $lockedHold = $this->lockHold($hold);
            if ($lockedHold->status === 'released' && $lockedHold->releaseJournal) {
                return $lockedHold->releaseJournal;
            }
            if ($lockedHold->status !== 'held') {
                throw new RuntimeException('Hold tidak dapat dilepas karena statusnya '.$lockedHold->status.'.');
            }

            $wallet = WalletAccount::whereKey($lockedHold->wallet_account_id)->lockForUpdate()->firstOrFail();
            $minor = $this->toMinorUnits($lockedHold->amount);
            $held = $this->toMinorUnits($wallet->held_balance);
            if ($held < $minor) {
                throw new RuntimeException('Saldo held wallet tidak konsisten.');
            }

            $journal = $this->findIdempotent($idempotencyKey);
            if (!$journal) {
                $journal = $this->createJournal(
                    $wallet,
                    'release',
                    $minor,
                    $idempotencyKey,
                    array_merge($metadata, ['hold_reference' => $lockedHold->hold_reference]),
                    'Melepas hold wallet',
                );
                $newHeld = $held - $minor;
                $newAvailable = $this->toMinorUnits($wallet->available_balance) + $minor;
                $wallet->forceFill([
                    'held_balance' => $this->fromMinorUnits($newHeld),
                    'available_balance' => $this->fromMinorUnits($newAvailable),
                ])->save();
                $this->createEntries($journal, $wallet, $minor, 'debit', 'wallet_available', 'credit', $newAvailable, $metadata, 'wallet_held');
                $journal->forceFill(['status' => 'posted', 'posted_at' => now()])->save();
            }

            $lockedHold->forceFill([
                'status' => 'released',
                'release_journal_id' => $journal->getKey(),
                'released_at' => now(),
            ])->save();

            return $journal->fresh('entries');
        });
    }

    public function refund(WalletJournal $original, ?string $idempotencyKey = null, array $metadata = []): WalletJournal
    {
        return DB::transaction(function () use ($original, $idempotencyKey, $metadata): WalletJournal {
            $lockedOriginal = WalletJournal::whereKey($original->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedOriginal->status !== 'posted') {
                throw new RuntimeException('Jurnal asal belum berstatus posted.');
            }
            if ($lockedOriginal->reversed_at) {
                throw new RuntimeException('Jurnal asal sudah dibalik.');
            }

            $wallet = WalletAccount::where('siswa_id', $lockedOriginal->siswa_id)->lockForUpdate()->firstOrFail();
            $refund = $this->credit(
                $wallet,
                $lockedOriginal->amount,
                'refund',
                'refund_liability',
                $idempotencyKey,
                array_merge($metadata, ['original_reference' => $lockedOriginal->reference]),
                'Pengembalian dana wallet',
            );
            $lockedOriginal->forceFill(['reversed_at' => now()])->save();
            return $refund;
        });
    }

    private function moveAvailable(
        WalletAccount|int $account,
        int|float|string $amount,
        string $type,
        string $walletDirection,
        string $counterpartyCode,
        ?string $idempotencyKey,
        array $metadata,
        ?string $description,
    ): WalletJournal {
        $minor = $this->toMinorUnits($amount);
        if ($minor <= 0) {
            throw new InvalidArgumentException('Nominal wallet harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($account, $minor, $type, $walletDirection, $counterpartyCode, $idempotencyKey, $metadata, $description): WalletJournal {
            if ($existing = $this->findIdempotent($idempotencyKey)) {
                return $existing->load('entries');
            }

            $wallet = $this->lockAccount($account);
            $this->ensureActive($wallet);
            $available = $this->toMinorUnits($wallet->available_balance);
            $newAvailable = $walletDirection === 'credit' ? $available + $minor : $available - $minor;
            if ($newAvailable < 0) {
                throw new RuntimeException('Saldo wallet tidak mencukupi.');
            }

            $journal = $this->createJournal($wallet, $type, $minor, $idempotencyKey, $metadata, $description);
            $wallet->forceFill(['available_balance' => $this->fromMinorUnits($newAvailable)])->save();
            $counterDirection = $walletDirection === 'credit' ? 'debit' : 'credit';
            $this->createEntries($journal, $wallet, $minor, $walletDirection, $counterpartyCode, $counterDirection, $newAvailable, $metadata, 'wallet_available');
            $journal->forceFill(['status' => 'posted', 'posted_at' => now()])->save();

            return $journal->fresh('entries');
        });
    }

    private function createJournal(WalletAccount $wallet, string $type, int $minor, ?string $idempotencyKey, array $metadata, ?string $description): WalletJournal
    {
        return WalletJournal::create([
            'reference' => 'WAL-'.now()->format('YmdHis').'-'.Str::upper(Str::random(10)),
            'type' => $type,
            'status' => 'pending',
            'siswa_id' => $wallet->siswa_id,
            'amount' => $this->fromMinorUnits($minor),
            'idempotency_key' => $idempotencyKey,
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function createEntries(WalletJournal $journal, WalletAccount $wallet, int $minor, string $walletDirection, string $counterpartyCode, string $counterpartyDirection, int $balanceAfter, array $metadata, string $walletAccountCode): void
    {
        WalletJournal::withoutEvents(function () use ($journal, $wallet, $minor, $walletDirection, $counterpartyCode, $counterpartyDirection, $balanceAfter, $metadata, $walletAccountCode): void {
            $journal->entries()->createMany([
                [
                    'wallet_account_id' => $wallet->getKey(),
                    'account_code' => $walletAccountCode,
                    'direction' => $walletDirection,
                    'amount' => $this->fromMinorUnits($minor),
                    'balance_after' => $this->fromMinorUnits($balanceAfter),
                    'metadata' => $metadata ?: null,
                ],
                [
                    'wallet_account_id' => null,
                    'account_code' => $counterpartyCode,
                    'direction' => $counterpartyDirection,
                    'amount' => $this->fromMinorUnits($minor),
                    'balance_after' => null,
                    'metadata' => $metadata ?: null,
                ],
            ]);
        });
    }

    private function lockAccount(WalletAccount|int $account): WalletAccount
    {
        $id = $account instanceof WalletAccount ? $account->getKey() : (int) $account;
        return WalletAccount::whereKey($id)->lockForUpdate()->firstOrFail();
    }

    private function lockHold(WalletHold|string $hold): WalletHold
    {
        $query = WalletHold::query();
        if ($hold instanceof WalletHold) {
            $query->whereKey($hold->getKey());
        } else {
            $query->where('hold_reference', $hold);
        }

        return $query->lockForUpdate()->with('captureJournal', 'releaseJournal')->firstOrFail();
    }

    private function findIdempotent(?string $key): ?WalletJournal
    {
        return $key ? WalletJournal::where('idempotency_key', $key)->first() : null;
    }

    private function ensureActive(WalletAccount $wallet): void
    {
        if ($wallet->status !== 'active') {
            throw new RuntimeException('Wallet tidak aktif.');
        }
    }

    private function toMinorUnits(int|float|string $amount): int
    {
        $value = trim((string) $amount);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Nominal wallet harus berupa angka positif dengan maksimal dua desimal.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromMinorUnits(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }
}
