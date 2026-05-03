<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'amount')) {
                $table->decimal('amount', 18, 2)->default(0)->after('category_id');
            }
            if (!Schema::hasColumn('expenses', 'reason')) {
                $table->string('reason')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('expenses', 'note')) {
                $table->text('note')->nullable()->after('reason');
            }
        });

        DB::table('expenses')
            ->where('amount', 0)
            ->update(['amount' => DB::raw('total')]);

        if (Schema::hasTable('expense_items')) {
            $items = DB::table('expense_items')->orderBy('created_at')->get();

            foreach ($items as $item) {
                $matched = DB::table('expenses')
                    ->where('shop_id', $item->shop_id)
                    ->where('category_id', $item->category_id)
                    ->where(function ($query) use ($item): void {
                        $query
                            ->where('amount', $item->amount)
                            ->orWhere('total', $item->amount);
                    })
                    ->whereBetween('created_at', [
                        Carbon::parse($item->created_at)->subSeconds(2),
                        Carbon::parse($item->created_at)->addSeconds(2),
                    ])
                    ->first();

                if ($matched) {
                    DB::table('expenses')
                        ->where('id', $matched->id)
                        ->update([
                            'amount' => $item->amount,
                            'total' => $item->amount,
                            'note' => $matched->note ?: $item->note,
                            'updated_at' => $item->updated_at,
                        ]);
                    continue;
                }

                DB::table('expenses')->insert([
                    'id' => $item->id ?: (string) Str::uuid(),
                    'shop_id' => $item->shop_id,
                    'category_id' => $item->category_id,
                    'amount' => $item->amount,
                    'total' => $item->amount,
                    'reason' => null,
                    'note' => $item->note,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);
            }

            Schema::dropIfExists('expense_items');
        }
    }

    public function down(): void
    {
        Schema::create('expense_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'category_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['amount', 'reason', 'note']);
        });
    }
};

