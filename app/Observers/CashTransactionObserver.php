<?php

namespace App\Observers;

use App\Models\CashTransaction;
use App\Models\DailyCashSummary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashTransactionObserver
{
    /**
     * Handle the CashTransaction "created" event.
     */
    public function created(CashTransaction $transaction): void
    {
        if (!$transaction->user_id) {
            return;
        }

        if ($transaction->category === 'due_waiver' || $transaction->category === 'waiver') {
            return;
        }

        if (in_array($transaction->payment_method, ['bkash_nagad', 'bank'])) {
            return;
        }

        $amount = (double) $transaction->amount;
        $type = $transaction->type; // 'in' or 'out'
        $date = Carbon::parse($transaction->date_time)->toDateString();

        // 1. Update user cash balance
        $user = User::find($transaction->user_id);
        if ($user) {
            if ($type === 'in') {
                $user->increment('cash_balance', $amount);
            } else {
                $user->decrement('cash_balance', $amount);
            }
        }

        // 2. Update daily cash summary
        $this->updateDailySummary($transaction->user_id, $date, $type, $amount);
    }

    /**
     * Handle the CashTransaction "updated" event.
     */
    public function updated(CashTransaction $transaction): void
    {
        // If soft deleted, updated event might be fired before deleted, but we should handle restoration and updates carefully
        if ($transaction->isDirty('deleted_at') && $transaction->deleted_at !== null) {
            return;
        }

        if ($transaction->category === 'due_waiver' || $transaction->category === 'waiver') {
            return;
        }

        if (in_array($transaction->payment_method, ['bkash_nagad', 'bank'])) {
            return;
        }

        $userId = $transaction->user_id;
        $oldUserId = $transaction->getOriginal('user_id') ?? $userId;

        // Calculate original values
        $oldAmount = (double) ($transaction->getOriginal('amount') ?? 0);
        $oldType = $transaction->getOriginal('type') ?? 'in';
        $oldDateTime = $transaction->getOriginal('date_time');
        $oldDate = $oldDateTime ? Carbon::parse($oldDateTime)->toDateString() : null;

        // Calculate current values
        $newAmount = (double) $transaction->amount;
        $newType = $transaction->type;
        $newDate = Carbon::parse($transaction->date_time)->toDateString();

        // If user_id changed (rare, but handle it)
        if ($oldUserId !== $userId) {
            // Revert from old user
            $oldUser = User::find($oldUserId);
            if ($oldUser) {
                if ($oldType === 'in') {
                    $oldUser->decrement('cash_balance', $oldAmount);
                } else {
                    $oldUser->increment('cash_balance', $oldAmount);
                }
            }
            if ($oldDate) {
                $this->updateDailySummary($oldUserId, $oldDate, $oldType, -$oldAmount);
            }

            // Apply to new user
            $newUser = User::find($userId);
            if ($newUser) {
                if ($newType === 'in') {
                    $newUser->increment('cash_balance', $newAmount);
                } else {
                    $newUser->decrement('cash_balance', $newAmount);
                }
            }
            $this->updateDailySummary($userId, $newDate, $newType, $newAmount);
            return;
        }

        // Update user cash balance for the same user
        $user = User::find($userId);
        if ($user) {
            $oldNet = ($oldType === 'in') ? $oldAmount : -$oldAmount;
            $newNet = ($newType === 'in') ? $newAmount : -$newAmount;
            $diff = $newNet - $oldNet;

            if ($diff > 0) {
                $user->increment('cash_balance', $diff);
            } elseif ($diff < 0) {
                $user->decrement('cash_balance', abs($diff));
            }
        }

        // Update daily cash summaries
        if ($oldDate === $newDate) {
            if ($oldType === $newType) {
                // Same date, same type: just adjust by amount difference
                $diff = $newAmount - $oldAmount;
                $this->updateDailySummary($userId, $newDate, $newType, $diff);
            } else {
                // Same date, different type: subtract old, add new
                $this->updateDailySummary($userId, $oldDate, $oldType, -$oldAmount);
                $this->updateDailySummary($userId, $newDate, $newType, $newAmount);
            }
        } else {
            // Different date: subtract from old date, add to new date
            if ($oldDate) {
                $this->updateDailySummary($userId, $oldDate, $oldType, -$oldAmount);
            }
            $this->updateDailySummary($userId, $newDate, $newType, $newAmount);
        }
    }

    /**
     * Handle the CashTransaction "deleted" event.
     */
    public function deleted(CashTransaction $transaction): void
    {
        if (!$transaction->user_id) {
            return;
        }

        if ($transaction->category === 'due_waiver' || $transaction->category === 'waiver') {
            return;
        }

        if (in_array($transaction->payment_method, ['bkash_nagad', 'bank'])) {
            return;
        }

        $amount = (double) $transaction->amount;
        $type = $transaction->type;
        $date = Carbon::parse($transaction->date_time)->toDateString();

        // 1. Revert user cash balance
        $user = User::find($transaction->user_id);
        if ($user) {
            if ($type === 'in') {
                $user->decrement('cash_balance', $amount);
            } else {
                $user->increment('cash_balance', $amount);
            }
        }

        // 2. Revert daily summary
        $this->updateDailySummary($transaction->user_id, $date, $type, -$amount);
    }

    /**
     * Handle the CashTransaction "restored" event.
     */
    public function restored(CashTransaction $transaction): void
    {
        $this->created($transaction);
    }

    /**
     * Update the daily summary record.
     */
    private function updateDailySummary(int $userId, string $date, string $type, float $amountDiff): void
    {
        if ($amountDiff === 0.0) {
            return;
        }

        DB::transaction(function () use ($userId, $date, $type, $amountDiff) {
            $parsedDate = Carbon::parse($date);
            $summary = DailyCashSummary::firstOrCreate(
                ['user_id' => $userId, 'date' => $parsedDate],
                ['cash_in' => 0.0, 'cash_out' => 0.0]
            );

            // Fetch with lock to ensure thread safety
            $summary = DailyCashSummary::where('id', $summary->id)->lockForUpdate()->first();

            if ($type === 'in') {
                $summary->cash_in = (double) $summary->cash_in + $amountDiff;
            } else {
                $summary->cash_out = (double) $summary->cash_out + $amountDiff;
            }

            $summary->save();
        });
    }
}
