<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet_id')->index();
            $table->string('category');
            $table->uuid('channel_id')->index();
            $table->decimal('amount', 8, 2);
            $table->string("reference")->nullable(); // is category is ad_revenue reference will be ad activity id, if it is a payout or top up, reference will be flutterwave ref
            $table->string("status")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
