<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('t_iso2', function (Blueprint $table) {
            $table->unsignedBigInteger('counter')->default(0);
        });
    }
    public function down()
    {
        Schema::table('t_iso2', function (Blueprint $table) {
            $table->dropColumn('counter');
        });
    }
};
