<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('state-machine.tables.transitions', 'state_histories');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('field', 64);
            $table->string('from', 64)->nullable();
            $table->string('to', 64)->nullable();
            $table->json('custom_properties')->nullable();
            $table->nullableMorphs('responsible');
            $table->json('changed_attributes')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id', 'field']);
            $table->index(['model_type', 'model_id', 'field', 'to']);
        });
    }

    public function down(): void
    {
        $tableName = config('state-machine.tables.transitions', 'state_histories');

        Schema::dropIfExists($tableName);
    }
};
