<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id'); //foreign key
            $table->string('type')->nullable(); //thumbnail, original, etc
            $table->string('size')->nullable(); //size in bytes
            $table->text('path')->nullable(); //path to file
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
            $table->index(['file_id'], 'file_items_file_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_items');
    }
};
