<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 200);
            $table->string('phone', 70);
            $table->string('email', 100);
            $table->string('bio', 250);
            $table->string('profile_picture', 200)->nullable();
            $table->string('password', 200);
            $table->enum('status', ['active', 'inactive']);
            $table->dateTime('created');
            $table->dateTime('modified');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
};
