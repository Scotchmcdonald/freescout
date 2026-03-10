<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-module foreign keys that cannot be defined in individual module
 * migrations due to table ordering / circular dependencies.
 *
 * FKs added:
 *  - milestones.contract_id → cm_contracts.id
 *  - milestones.invoice_id  → pib_invoices.id
 *  - pib_invoices.contract_id → cm_contracts.id
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── milestones → cm_contracts ───────────────────────────────────
        if (Schema::hasTable('milestones') && Schema::hasTable('cm_contracts')) {
            if (Schema::hasColumn('milestones', 'contract_id')) {
                $this->addForeignKeyIfMissing('milestones', 'contract_id', 'cm_contracts', 'id', 'nullOnDelete');
            }
        }

        // ── milestones → pib_invoices ───────────────────────────────────
        if (Schema::hasTable('milestones') && Schema::hasTable('pib_invoices')) {
            if (Schema::hasColumn('milestones', 'invoice_id')) {
                $this->addForeignKeyIfMissing('milestones', 'invoice_id', 'pib_invoices', 'id', 'nullOnDelete');
            }
        }

        // ── pib_invoices → cm_contracts ─────────────────────────────────
        if (Schema::hasTable('pib_invoices') && Schema::hasTable('cm_contracts')) {
            if (Schema::hasColumn('pib_invoices', 'contract_id')) {
                $this->addForeignKeyIfMissing('pib_invoices', 'contract_id', 'cm_contracts', 'id', 'nullOnDelete');
            }
        }
    }

    /**
     * Add a foreign key only if one doesn't already exist on the column.
     */
    private function addForeignKeyIfMissing(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete = 'cascade',
    ): void {
        $existingFks = Schema::getForeignKeys($table);
        foreach ($existingFks as $fk) {
            if (in_array($column, $fk['columns'], true)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $referencedColumn, $onDelete) {
            $fk = $blueprint->foreign($column)->references($referencedColumn)->on($referencedTable);
            match ($onDelete) {
                'nullOnDelete' => $fk->nullOnDelete(),
                'cascadeOnDelete', 'cascade' => $fk->cascadeOnDelete(),
                default => $fk,
            };
        });
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
