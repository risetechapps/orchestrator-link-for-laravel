<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orchestrator_shipments', function (Blueprint $table) {
            $table->id();

            // Dados do Melhor Envio
            $table->string('me_order_id', 36)->nullable()->unique()->comment('UUID do pedido no Melhor Envio');
            $table->string('me_protocol', 50)->nullable()->comment('Protocolo ORD-xxx');
            $table->unsignedInteger('service_id')->nullable()->comment('ID do serviço/transportadora');
            $table->string('service_name', 100)->nullable();
            $table->string('status', 50)->default('pending')->comment('pending|released|posted|delivered|undelivered|canceled');
            $table->string('tracking_code', 50)->nullable()->comment('Código de rastreio (ex: ME23002OWZ7BR)');

            // Origem
            $table->string('from_postal_code', 8)->nullable();
            $table->json('from_data')->nullable()->comment('Dados completos do remetente');

            // Destino
            $table->string('to_postal_code', 8)->nullable();
            $table->json('to_data')->nullable()->comment('Dados completos do destinatário');

            // Pacote
            $table->json('products')->nullable();
            $table->json('volumes')->nullable();
            $table->json('options')->nullable();

            // Financeiro
            $table->decimal('price', 10, 2)->nullable()->comment('Valor do frete em R$');

            // Etiqueta
            $table->text('label_url')->nullable()->comment('URL do PDF da etiqueta');

            // Datas de ciclo de vida
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps();

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
