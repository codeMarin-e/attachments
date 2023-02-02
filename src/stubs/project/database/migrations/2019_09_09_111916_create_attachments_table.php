<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('site_id');
            $table->string('session_id')->nullable()->index();
            $table->unsignedBigInteger('attachable_id')->nullable();
            $table->string('attachable_type')->nullable();
            $table->string('disk')->nullable();
            $table->string('dir');
            $table->string('filename');
            $table->string('original_name');
            $table->string('extension');
            $table->string('type')->nullable();
            $table->string('size')->nullable();
            $table->unsignedTinyInteger('main')->default(0);
            $table->integer('ord');
            $table->timestamps();

            $table->index(['attachable_id', 'attachable_type']);
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attachments');
    }
};
