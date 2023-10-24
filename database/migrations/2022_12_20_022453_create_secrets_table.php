<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Luchavez\StarterKit\Enums\OnDeleteAction;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('secrets', static function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->owned(on_delete: OnDeleteAction::CASCADE); // from luchavez/starter-kit
            $table->unsignedTinyInteger('type');
            $table->string('value');
            $table->string('description')->nullable();
            $table->boolean('hashed');
            $table->usage(); // from luchavez/starter-kit
            $table->expires(); // from luchavez/starter-kit
            $table->disables(); // from luchavez/starter-kit
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('secrets');
    }
};
