<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('t_iso2', function (Blueprint $table) {
            $table->char('iso3', 3)
                ->nullable()
                ->after('iso2')
                ->unique();
        });
    }

    public function down()
    {
        Schema::table('t_iso2', function (Blueprint $table) {
            $table->dropUnique(['iso3']);
            $table->dropColumn('iso3');
        });
    }
};
