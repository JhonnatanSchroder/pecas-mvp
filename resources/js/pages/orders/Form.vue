<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as orderRoutes from '@/routes/orders';
type Part = { id: number; name: string; sku: string | null; price_cents: number; stock_quantity: number };
const props = defineProps<{ parts: Part[] }>();
const form = useForm({ items: [{ part_id: props.parts[0]?.id ?? 0, quantity: 1 }] });
function addItem(): void { form.items.push({ part_id: props.parts[0]?.id ?? 0, quantity: 1 }); }
function removeItem(index: number): void { form.items.splice(index, 1); }
function submit(): void { form.post(orderRoutes.store().url); }
function money(cents: number): string { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100); }
</script>
<template><Head title="Novo pedido" /><AppLayout :breadcrumbs="[{ title: 'Pedidos', href: orderRoutes.index() }, { title: 'Novo pedido', href: orderRoutes.create() }] "><div class="mx-auto max-w-3xl p-4"><div class="mb-6"><h1 class="text-2xl font-semibold">Novo pedido</h1><p class="text-sm text-muted-foreground">O preço é registrado a partir do catálogo no momento da criação.</p></div><form class="space-y-5 rounded-xl border p-6" @submit.prevent="submit"><p v-if="parts.length === 0" class="text-sm text-destructive">Cadastre uma peça ativa antes de criar um pedido.</p><div v-for="(item, index) in form.items" :key="index" class="grid gap-3 border-b pb-4 md:grid-cols-[1fr_120px_auto]"><label class="space-y-1"><span class="text-sm font-medium">Peça</span><select v-model.number="item.part_id" class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"><option v-for="part in parts" :key="part.id" :value="part.id">{{ part.name }}{{ part.sku ? ` (${part.sku})` : '' }} — {{ money(part.price_cents) }} · estoque {{ part.stock_quantity }}</option></select></label><label class="space-y-1"><span class="text-sm font-medium">Quantidade</span><Input v-model.number="item.quantity" min="1" type="number" /></label><Button class="self-end" :disabled="form.items.length === 1" size="icon" type="button" variant="ghost" @click="removeItem(index)"><Trash2 class="size-4" /></Button></div><p v-if="form.errors.items" class="text-sm text-destructive">{{ form.errors.items }}</p><Button :disabled="parts.length === 0" type="button" variant="outline" @click="addItem"><Plus class="size-4" /> Adicionar peça</Button><div class="flex justify-end gap-3"><Button as-child variant="outline"><Link :href="orderRoutes.index()">Cancelar</Link></Button><Button :disabled="form.processing || parts.length === 0" type="submit">Criar pedido</Button></div></form></div></AppLayout></template>
