<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToBastFixedAssetsTable extends Migration
{
    public function up()
    {
        Schema::table('bast_fixed_assets', function (Blueprint $table) {
            $table->string('id_checker')->nullable();
        });
    }

    public function down()
    {
        Schema::table('bast_fixed_assets', function (Blueprint $table) {
            $table->dropColumn(['id_checker']);
        });
    }
}
