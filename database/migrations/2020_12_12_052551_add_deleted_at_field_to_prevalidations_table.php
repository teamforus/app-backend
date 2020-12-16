<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddDeletedAtFieldToPrevalidationsTable
 * @noinspection PhpUnused
 */
class AddDeletedAtFieldToPrevalidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('prevalidations', function (Blueprint $table) {
            $table->softDeletes()->after('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('prevalidations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
