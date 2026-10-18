<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as orderRoutes from '@/routes/orders';
type Order = { id: number; status: string; created_at: string; items: unknown[] };
defineProps<{ orders: Order[] }>();
const statusLabel: Record<string, string> = { pending: 'Pendente', confirmed: 'Confirmado', cancelled: 'Cancelado' };
</script>
<template><Head title="Pedidos" /><AppLayout :breadcrumbs="[{ title: 'Pedidos', href: orderRoutes.index() }]"><div class="space-y-6 p-4"><div class="flex items-center justify-between gap-4"><div><h1 class="text-2xl font-semibold">Pedidos</h1><p class="text-sm text-muted-foreground">Acompanhe vendas e movimentações de estoque.</p></div><Button as-child><Link :href="orderRoutes.create()"><Plus class="size-4" /> Novo pedido</Link></Button></div><Card class="overflow-hidden p-0"><div v-if="orders.length === 0" class="p-8 text-center text-sm text-muted-foreground">Nenhum pedido encontrado.</div><table v-else class="w-full text-sm"><thead class="border-b bg-muted/50 text-left text-muted-foreground"><tr><th class="p-4">Pedido</th><th class="p-4">Data</th><th class="p-4">Itens</th><th class="p-4">Status</th></tr></thead><tbody><tr v-for="order in orders" :key="order.id" class="border-b last:border-0"><td class="p-4 font-medium"><Link class="hover:underline" :href="orderRoutes.show(order.id)">#{{ order.id }}</Link></td><td class="p-4">{{ new Date(order.created_at).toLocaleString('pt-BR') }}</td><td class="p-4">{{ order.items.length }}</td><td class="p-4"><Badge :variant="order.status === 'cancelled' ? 'secondary' : 'default'">{{ statusLabel[order.status] }}</Badge></td></tr></tbody></table></Card></div></AppLayout></template>
