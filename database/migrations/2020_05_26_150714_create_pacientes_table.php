<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePacientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('second_name')->nullable();
            $table->string('last_name');
            $table->string('second_last_name')->nullable();
            $table->string('dni');
            $table->date('birth_date');
            $table->string('address');
            $table->string('phone')->nullable();
            $table->boolean('sex');
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->json('medical_history')->nullable();
            $table->json('procedures')->nullable();
            $table->foreignId('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('registered_by')->references('id')->on('users')->onDelete('cascade'); // Hacemos referencia a la persona que introdujo los datos
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
        Schema::dropIfExists('pacients');
    }
}
