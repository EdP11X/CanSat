<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_frames', function (Blueprint $table) {
            $table->id();
            $table->string('url', 255);          // ruta del archivo en ./frames
            $table->integer('num')->nullable();  // número de frame opcional
            $table->float('lat')->nullable();
            $table->float('lon')->nullable();
            $table->timestamp('timestamp')->useCurrent(); // fecha/hora de captura
        });
    }

    /*public function down(): void
    {
        Schema::dropIfExists('image_frames');
    }*/
};
