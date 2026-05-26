<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mantax559\LaravelFiles\Enums\FileExtension;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('laravel-files.table'), function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('path');
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('size_bits');
            $table->enum('extension', array_column(FileExtension::cases(), 'value'));
            $table->string('disk');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('checksum')->nullable();
            $table->unique(['disk', 'path']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('laravel-files.table'));
    }
};
