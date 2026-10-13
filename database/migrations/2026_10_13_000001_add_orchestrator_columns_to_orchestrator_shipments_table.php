<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frete white label (orchestrator 4.0): o envio passa a ser identificado pelo
 * id do orchestrator. As colunas me_* ficam para os registros antigos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orchestrator_shipments', function (Blueprint $table) {
            $table->uuid('orchestrator_id')->nullable()->unique()->after('id')->comment('Id do envio no orchestrator');
            $table->string('external_reference', 100)->nullable()->after('orchestrator_id');
            $table->string('carrier', 100)->nullable()->after('service_name');
            $table->text('tracking_url')->nullable()->after('tracking_code')->comment('Página pública de rastreio');
            $table->boolean('sandbox')->default(false)->after('status')->comment('Criado com a chave de teste');
            $table->timestamp('paid_at')->nullable()->after('label_url');

            $table->index('external_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orchestrator_shipments', function (Blueprint $table) {
            $table->dropIndex(['external_reference']);
            $table->dropUnique(['orchestrator_id']);
            $table->dropColumn(['orchestrator_id', 'external_reference', 'carrier', 'tracking_url', 'sandbox', 'paid_at']);
        });
    }
};
