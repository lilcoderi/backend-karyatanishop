<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderIdToReviewsTable extends Migration
{
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Menambahkan kolom order_id dengan tipe yang sesuai
            $table->unsignedBigInteger('order_id')->nullable()->after('rating'); 

            // Menambahkan foreign key constraint
            $table->foreign('order_id')
                  ->references('order_id') // Kolom di tabel orders
                  ->on('orders') // Tabel orders
                  ->onDelete('cascade'); // Aturan penghapusan
        });
    }

    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }
}

