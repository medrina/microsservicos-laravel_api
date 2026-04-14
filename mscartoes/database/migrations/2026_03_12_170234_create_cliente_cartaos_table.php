<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cliente_cartaos', function (Blueprint $table) {
            $table->id();
            $table->uuid('id_cliente');
            $table->uuid('id_cartao');
            $table->decimal('renda', 10, 2);
            $table->decimal('limiteBasico', 10);
            
            // Definindo uma combinação das duas colunas abaixo como uma chave única
            $table->unique(['id_cliente', 'id_cartao']);

            // Opcional: Adicionar índices para performance de busca
            $table->index('id_cliente');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_cartaos');
    }
};
