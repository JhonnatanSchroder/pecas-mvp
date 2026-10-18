<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as orderRoutes from '@/routes/orders';
type OrderItem = { id: number; part_name: string; quantity: number; unit_price_cents: number };
type Order = { id: number; status: string; created_at: string; items: OrderItem[] };
const props = defineProps<{ order: Order }>();
const label: Record<string, string> = { pending: 'Pendente', confirmed: 'Confirmado', cancelled: 'Cancelado' };
function money(cents: number): string { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100); }
function confirm(): void { router.post(orderRoutes.confirm(props.order.id).url); }
function cancel(): void { if (window.confirm('Cancelar este pedido?')) router.post(orderRoutes.cancel(props.order.id).url); }
</script>
<template><Head :title="`Pedido #${order.id}`" /><AppLayout :breadcrumbs="[{ title: 'Pedidos', href: orderRoutes.index() }, { title: `Pedido #${order.id}`, href: orderRoutes.show(order.id) }] "><div class="mx-auto max-w-3xl space-y-6 p-4"><div class="flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-semibold">Pedido #{{ order.id }}</h1><p class="text-sm text-muted-foreground">Criado em {{ new Date(order.created_at).toLocaleString('pt-BR') }}</p></div><Badge :variant="order.status === 'cancelled' ? 'secondary' : 'default'">{{ label[order.status] }}</Badge></div><Card class="overflow-hidden p-0"><table class="w-full text-sm"><thead class="border-b bg-muted/50 text-left text-muted-foreground"><tr><th class="p-4">Peça</th><th class="p-4">Quantidade</th><th class="p-4">Preço unitário</th><th class="p-4 text-right">Subtotal</th></tr></thead><tbody><tr v-for="item in order.items" :key="item.id" class="border-b last:border-0"><td class="p-4 font-medium">{{ item.part_name }}</td><td class="p-4">{{ item.quantity }}</td><td class="p-4">{{ money(item.unit_price_cents) }}</td><td class="p-4 text-right">{{ money(item.unit_price_cents * item.quantity) }}</td></tr></tbody></table></Card><div v-if="order.status === 'pending'" class="flex justify-end gap-3"><Button variant="outline" @click="cancel">Cancelar pedido</Button><Button @click="confirm">Confirmar e baixar estoque</Button></div><div v-else-if="order.status === 'confirmed'" class="flex justify-end"><Button variant="outline" @click="cancel">Cancelar e devolver estoque</Button></div><Button as-child variant="outline"><Link :href="orderRoutes.index()">Voltar para pedidos</Link></Button></div></AppLayout></template>
