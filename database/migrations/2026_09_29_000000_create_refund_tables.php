<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('customer_id')->constrained();
            $t->date('placed_at');
            $t->string('item');
            $t->decimal('total', 10, 2);
            $t->boolean('final_sale')->default(false);
            $t->string('status');
            $t->timestamps();
        });
        Schema::create('refund_requests', function (Blueprint $t) {
            $t->id();
            $t->string('email');
            $t->string('order_id');
            $t->text('message');
            $t->string('decision');
            $t->string('reason_category');
            $t->json('rules_fired');
            $t->json('llm_analysis');
            $t->text('reply');
            $t->boolean('llm_used')->default(false);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
    }
};
