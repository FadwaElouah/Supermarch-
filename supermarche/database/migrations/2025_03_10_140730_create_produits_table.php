<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rayon_id')->constrained()->onDelete('cascade');
            $table->foreignId('categorie_id')->constrained()->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('prix', 10, 2);
            $table->string('image_path')->nullable();
            $table->boolean('est_populaire')->default(false);
            $table->boolean('est_en_promotion')->default(false);
            $table->decimal('prix_promotion', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('produits');
    }
};
