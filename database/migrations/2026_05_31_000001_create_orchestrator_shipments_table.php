<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cópia local dos envios do orchestrator (Shipment::syncFromOrchestrator).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orchestrator_shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('orchestrator_id')->unique()->comment('Id do envio no orchestrator');
            $table->string('external_reference', 100)->nullable()->comment('Referência da app (ex.: id do pedido)');

            // Serviço
            $table->unsignedInteger('service_id')->nullable();
            $table->string('service_name', 100)->nullable();
            $table->string('carrier', 100)->nullable();
            $table->string('status', 50)->default('pending')
                ->comment('pending|awaiting_payment|processing|purchased|label_ready|posted|delivered|undelivered|cancelled');
            $table->boolean('sandbox')->default(false)->comment('Criado com a chave de teste');
            $table->string('tracking_code', 50)->nullable();
            $table->text('tracking_url')->nullable()->comment('Página pública de rastreio');

            // Origem
            $table->string('from_postal_code', 8)->nullable();
            $table->json('from_data')->nullable()->comment('Dados completos do remetente');

            // Destino
            $table->string('to_postal_code', 8)->nullable();
            $table->json('to_data')->nullable()->comment('Dados completos do destinatário');

            // Pacote, como foi enviado na criação
            $table->json('products')->nullable();
            $table->json('volumes')->nullable();
            $table->json('options')->nullable();

            $table->decimal('price', 10, 2)->nullable()->comment('Valor do frete em R$');

            // Datas de ciclo de vida
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('external_reference');
            $table->index('status');
            $table->index('from_postal_code');
            $table->index('to_postal_code');
            $table->index('tracking_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orchestrator_shipments');
    }
};
