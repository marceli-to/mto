<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The rate ladder as of September 2026. */
    private const RATES = [
        'Reduced'  => 125.00,
        'Standard' => 140.00,
        'Premium'  => 150.00,
    ];

    /** What the two existing tiers were worth before. */
    private const PREVIOUS = [
        'Reduced'  => 100.00,
        'Standard' => 125.00,
    ];

    public function up(): void
    {
        foreach (self::RATES as $description => $amount) {
            $this->setAmount($description, $amount);
        }
    }

    public function down(): void
    {
        foreach (self::PREVIOUS as $description => $amount) {
            DB::table('rates')->where('description', $description)->update([
                'amount'     => $amount,
                'updated_at' => now(),
            ]);
        }

        // Only drop the new tier if nothing came to depend on it.
        $premium = DB::table('rates')->where('description', 'Premium')->first();
        if ($premium && !DB::table('projects')->where('rate_id', $premium->id)->exists()) {
            DB::table('rates')->where('id', $premium->id)->delete();
        }
    }

    /** Matched by label so the migration is idempotent and safe to run anywhere. */
    private function setAmount(string $description, float $amount): void
    {
        $rate = DB::table('rates')->where('description', $description)->first();

        if ($rate) {
            DB::table('rates')->where('id', $rate->id)->update([
                'amount'     => $amount,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('rates')->insert([
            'description' => $description,
            'amount'      => $amount,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
};
