<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kernel_boiler_softener_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('office', ['YBS', 'SUN', 'SJN'])->nullable();
            $table->dateTime('rounded_time')->nullable();
            $table->enum('jenis', ['boiler', 'softener']);
            $table->string('parameter');
            $table->decimal('nilai', 12, 4);
            $table->string('satuan')->nullable();
            $table->string('operator')->nullable();
            $table->string('sampel_boy')->nullable();
            $table->boolean('pengulangan')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['office', 'jenis', 'parameter']);
            $table->index(['rounded_time', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kernel_boiler_softener_calculations');
    }
};