<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Prevalidation;

class UpdatePrevalidationHashes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Prevalidation::whereHas('fund.fund_config')->with([
            'fund.fund_config', 'prevalidation_records.record_type',
        ])->get()->each(static function(Prevalidation $prevalidation) {
            $prevalidation->updateHashes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
}
