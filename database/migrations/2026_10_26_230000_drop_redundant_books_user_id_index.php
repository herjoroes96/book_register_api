<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('books', 'books_user_id_index')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropIndex('books_user_id_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasIndex('books', 'books_user_id_index')) {
            Schema::table('books', function (Blueprint $table) {
                $table->index('user_id');
            });
        }
    }
};
