<?php

use App\Models\Episode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listening_partie', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Episode::class)->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_active')->default(true);
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listening_party.phps');
    }
};
