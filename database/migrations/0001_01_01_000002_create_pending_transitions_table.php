<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('state-machine.tables.pending_transitions', 'pending_transitions');

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
            $table->timestamp('transition_at');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['applied_at', 'transition_at']);
        });
    }

    public function down(): void
    {
        $tableName = config('state-machine.tables.pending_transitions', 'pending_transitions');

        Schema::dropIfExists($tableName);
    }
};
