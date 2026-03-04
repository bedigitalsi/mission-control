<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->string('phone_number');
            $table->text('message');
            $table->string('sender_name')->nullable(); // alex, sarah, brandon, customer, etc.
            $table->string('status')->default('delivered'); // sent, delivered, failed, received
            $table->string('provider')->nullable(); // android, smsapi
            $table->string('from_name')->nullable(); // branded sender (Zavedno, etc.)
            $table->string('device_id')->nullable();
            $table->string('external_id')->nullable(); // sms gateway message id
            $table->json('meta')->nullable(); // extra data
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['phone_number', 'created_at']);
            $table->index(['direction', 'created_at']);
            $table->index('sender_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
