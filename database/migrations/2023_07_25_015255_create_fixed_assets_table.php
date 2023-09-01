<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixedAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_sub_grup')->constrained('sub_groups');
            $table->string('nama');
            $table->string('brand');
            $table->string('kode_aktiva');
            $table->string('kode_penyusutan');
            $table->string('nomor');
            $table->string('masa_manfaat');
            $table->date('tgl_perolehan');
            $table->decimal('nilai_perolehan', 30, 2);
            $table->decimal('nilai_depresiasi_awal', 30, 2);
            $table->foreignId('id_lokasi')->constrained('locations')->nullable();
            $table->string('id_departemen');
            $table->string('id_pic');
            $table->string('cost_centre');
            $table->string('kondisi');
            $table->foreignId('id_supplier')->constrained('suppliers');
            $table->foreignId('id_kode_adjustment')->constrained('adjustments');
            $table->jsonb('spesifikasi');
            $table->string('keterangan');
            $table->integer('status');
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
        Schema::dropIfExists('fixed_assets');
    }
}
