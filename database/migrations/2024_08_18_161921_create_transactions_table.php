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
            $table->id();
            $table->string('invoice_id');
            $table->string('invoice_status');
            $table->string('invoice_reference');
            $table->decimal('invoice_value', 10, 2);
            $table->decimal('due_deposit', 10, 2)->nullable();
            $table->string('deposit_status')->nullable();
            $table->string('invoice_display_value');
            $table->string('customer_name');
            $table->string('customer_mobile');
            $table->string('customer_email');
            $table->string('customer_reference');
            $table->string('transaction_id');
            $table->string('payment_gateway');
            $table->string('transaction_status');
            $table->timestamp('transaction_date');
            $table->string('reference_id')->nullable();
            $table->string('track_id')->nullable();
            $table->string('authorization_id')->nullable();
            $table->decimal('transaction_value', 10, 2);
            $table->string('paid_currency');
            $table->decimal('paid_currency_value', 10, 2);
            $table->decimal('total_service_charge', 10, 2)->nullable();
            $table->decimal('vat_amount', 10, 2)->nullable();
            $table->string('ip_address')->nullable();
            $table->string('country')->nullable();
            $table->text('invoice_error')->nullable();
            $table->string('error_code')->nullable();
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