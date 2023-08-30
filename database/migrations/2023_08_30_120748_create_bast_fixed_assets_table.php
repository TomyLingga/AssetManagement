<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBastFixedAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bast_fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_fixed_asset')->constrained('fixed_assets');
            $table->date('tgl_serah');
            $table->string('nomor_serah');
            $table->string('id_user');
            $table->string('id_pic');
            $table->date('ttd_terima')->nullable();
            $table->date('ttd_checker')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bast_fixed_assets');
    }
}
