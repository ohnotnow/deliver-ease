<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->string('pin_hash')->after('name');
            $table->string('pin_hint', 4)->nullable()->after('pin_hash');

            if (Schema::hasColumn('runs', 'pin')) {
                $table->dropColumn('pin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->string('pin', 6)->after('name');

            $table->dropColumn(['pin_hash', 'pin_hint']);
        });
    }
};
