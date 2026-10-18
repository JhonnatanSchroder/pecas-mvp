<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Warehouse } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import inventoryAdjustmentRoutes from '@/routes/parts/inventory-adjustment';
import * as partRoutes from '@/routes/parts';

type Part = { id: number; name: string; stock_quantity: number };
type Movement = {
    id: number;
    type: 'initial' | 'adjustment';
    quantity_delta: number;
    balance_after: number;
    reason: string | null;
    user_name: string | null;
    created_at: string;
};

defineProps<{
    part: Part;
    movements: Movement[];
    canAdjustInventory: boolean;
}>();

function typeLabel(type: Movement['type']): string {
    return type === 'initial' ? 'Estoque inicial' : 'Ajuste';
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>

<template>
    <Head :title="`Histórico · ${part.name}`" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Peças', href: partRoutes.index() },
            { title: 'Histórico', href: partRoutes.inventoryHistory(part.id) },
        ]"
    >
        <div class="space-y-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">Histórico de estoque</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ part.name }} · Saldo atual: {{ part.stock_quantity }}
                    </p>
                </div>
                <Button v-if="canAdjustInventory" as-child
                    ><Link :href="inventoryAdjustmentRoutes.create(part.id)"
                        ><Plus class="size-4" /> Ajustar estoque</Link
                    ></Button
                >
            </div>
            <Card class="overflow-hidden p-0">
                <div
                    v-if="movements.length === 0"
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    Ainda não há movimentações para esta peça.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b bg-muted/50 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="p-4 font-medium">Tipo</th>
                                <th class="p-4 font-medium">Variação</th>
                                <th class="p-4 font-medium">Saldo após</th>
                                <th class="p-4 font-medium">Motivo</th>
                                <th class="p-4 font-medium">Usuário</th>
                                <th class="p-4 font-medium">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="movement in movements"
                                :key="movement.id"
                                class="border-b last:border-0"
                            >
                                <td class="p-4">
                                    {{ typeLabel(movement.type) }}
                                </td>
                                <td
                                    class="p-4"
                                    :class="
                                        movement.quantity_delta > 0
                                            ? 'text-emerald-600'
                                            : 'text-destructive'
                                    "
                                >
                                    {{ movement.quantity_delta > 0 ? '+' : ''
                                    }}{{ movement.quantity_delta }}
                                </td>
                                <td class="p-4">
                                    {{ movement.balance_after }}
                                </td>
                                <td class="p-4">
                                    {{ movement.reason ?? '—' }}
                                </td>
                                <td class="p-4">
                                    {{ movement.user_name ?? '—' }}
                                </td>
                                <td class="p-4">
                                    {{ formatDate(movement.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
            <Button variant="outline" as-child
                ><Link :href="partRoutes.index()"
                    ><Warehouse class="size-4" /> Voltar ao catálogo</Link
                ></Button
            >
        </div>
    </AppLayout>
</template>
