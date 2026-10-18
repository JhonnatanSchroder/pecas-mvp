<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import inventoryAdjustmentRoutes from '@/routes/parts/inventory-adjustment';
import * as partRoutes from '@/routes/parts';

type Part = { id: number; name: string; stock_quantity: number };

const props = defineProps<{ part: Part }>();
const form = useForm({ quantity_delta: 0, reason: '' });

function submit(): void {
    form.post(inventoryAdjustmentRoutes.store(props.part.id).url);
}
</script>

<template>
    <Head title="Ajustar estoque" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Peças', href: partRoutes.index() },
            { title: 'Histórico', href: partRoutes.inventoryHistory(part.id) },
            {
                title: 'Ajustar estoque',
                href: inventoryAdjustmentRoutes.create(part.id),
            },
        ]"
    >
        <div class="mx-auto max-w-xl p-4">
            <h1 class="text-2xl font-semibold">Ajustar estoque</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ part.name }} · Saldo atual: {{ part.stock_quantity }}
            </p>
            <form
                class="mt-6 space-y-5 rounded-xl border p-6"
                @submit.prevent="submit"
            >
                <label class="block space-y-2">
                    <span class="text-sm font-medium">Variação</span>
                    <Input
                        v-model.number="form.quantity_delta"
                        type="number"
                        step="1"
                    />
                    <span class="text-xs text-muted-foreground"
                        >Use valor positivo para entrada e negativo para
                        saída.</span
                    >
                    <span
                        v-if="form.errors.quantity_delta"
                        class="block text-sm text-destructive"
                        >{{ form.errors.quantity_delta }}</span
                    >
                </label>
                <label class="block space-y-2">
                    <span class="text-sm font-medium">Motivo</span>
                    <textarea
                        v-model="form.reason"
                        class="min-h-28 w-full rounded-md border bg-transparent p-3 text-sm"
                    />
                    <span
                        v-if="form.errors.reason"
                        class="block text-sm text-destructive"
                        >{{ form.errors.reason }}</span
                    >
                </label>
                <div class="flex justify-end gap-3">
                    <Button variant="outline" as-child
                        ><Link :href="partRoutes.inventoryHistory(part.id)"
                            >Cancelar</Link
                        ></Button
                    >
                    <Button :disabled="form.processing" type="submit"
                        >Confirmar ajuste</Button
                    >
                </div>
            </form>
        </div>
    </AppLayout>
</template>
