<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_data', function (Blueprint $t) {
            $t->increments('id');

            $t->dateTime('timestamp')->nullable()->index('ts_idx');

            $t->double('temp_int')->nullable();
            $t->double('temp_ext')->nullable();
            $t->double('humedad')->nullable();
            $t->double('presion')->nullable();
            $t->double('gas')->nullable();

            $t->double('co')->nullable();
            $t->double('h2')->nullable();
            $t->double('ch4')->nullable();
            $t->double('c2h')->nullable();
            $t->double('no2')->nullable();

            $t->double('ax')->nullable();
            $t->double('ay')->nullable();
            $t->double('az')->nullable();

            $t->double('vx')->nullable();
            $t->double('vy')->nullable();
            $t->double('vz')->nullable();

            $t->double('pitch')->nullable();
            $t->double('roll')->nullable();
            $t->double('yaw')->nullable();

            $t->double('lat')->nullable();
            $t->double('lon')->nullable();

            $t->double('a_abs_ms2')->nullable();
            $t->double('altitud_m')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};
