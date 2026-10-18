<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as partRoutes from '@/routes/parts';

type Part = {
    id: number;
    name: string;
    phone_brand: string;
    phone_model: string;
    sku: string | null;
    price_cents: number;
    stock_quantity: number;
    aliases: string[] | null;
    is_active: boolean;
};

const props = defineProps<{ part: Part | null }>();
const form = useForm({
    name: props.part?.name ?? '',
    phone_brand: props.part?.phone_brand ?? '',
    phone_model: props.part?.phone_model ?? '',
    sku: props.part?.sku ?? '',
    price_cents: props.part?.price_cents ?? 0,
    stock_quantity: props.part?.stock_quantity ?? 0,
    aliases: props.part?.aliases?.join('\n') ?? '',
    is_active: props.part?.is_active ?? true,
});

function submit(): void {
    form.transform(({ stock_quantity, ...data }) => ({
        ...data,
        sku: data.sku || null,
        aliases: data.aliases
            .split('\n')
            .map((alias) => alias.trim())
            .filter(Boolean),
        ...(props.part ? {} : { stock_quantity }),
    }));

    if (props.part) {
        form.put(partRoutes.update(props.part.id).url);
        return;
    }

    form.post(partRoutes.store().url);
}

function formatPrice(cents: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((Number(cents) || 0) / 100);
}
</script>

<template>
    <Head :title="part ? 'Editar peça' : 'Nova peça'" />

    <AppLayout
        :breadcrumbs="[
            { title: 'Peças', href: partRoutes.index() },
            {
                title: part ? 'Editar peça' : 'Nova peça',
                href: part ? partRoutes.edit(part.id) : partRoutes.create(),
            },
        ]"
    >
        <div class="mx-auto max-w-3xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold">
                    {{ part ? 'Editar peça' : 'Nova peça' }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Os valores são informados e armazenados em centavos.
                </p>
            </div>

            <form
                class="space-y-5 rounded-xl border p-6"
                @submit.prevent="submit"
            >
                <div class="grid gap-5 md:grid-cols-2">
                    <label class="space-y-2 md:col-span-2"
                        ><span class="text-sm font-medium">Nome</span
                        ><Input v-model="form.name" /><span
                            v-if="form.errors.name"
                            class="text-sm text-destructive"
                            >{{ form.errors.name }}</span
                        ></label
                    >
                    <label v-if="!part" class="space-y-2"
                        ><span class="text-sm font-medium"
                            >Marca do celular</span
                        ><Input v-model="form.phone_brand" /><span
                            v-if="form.errors.phone_brand"
                            class="text-sm text-destructive"
                            >{{ form.errors.phone_brand }}</span
                        ></label
                    >
                    <label class="space-y-2"
                        ><span class="text-sm font-medium"
                            >Modelo do celular</span
                        ><Input v-model="form.phone_model" /><span
                            v-if="form.errors.phone_model"
                            class="text-sm text-destructive"
                            >{{ form.errors.phone_model }}</span
                        ></label
                    >
                    <label class="space-y-2"
                        ><span class="text-sm font-medium">SKU (opcional)</span
                        ><Input v-model="form.sku" /><span
                            v-if="form.errors.sku"
                            class="text-sm text-destructive"
                            >{{ form.errors.sku }}</span
                        ></label
                    >
                    <label class="space-y-2"
                        ><span class="text-sm font-medium"
                            >Preço em centavos</span
                        ><Input
                            v-model.number="form.price_cents"
                            type="number"
                            min="0"
                        /><span class="text-xs text-muted-foreground">{{
                            formatPrice(form.price_cents)
                        }}</span
                        ><span
                            v-if="form.errors.price_cents"
                            class="block text-sm text-destructive"
                            >{{ form.errors.price_cents }}</span
                        ></label
                    >
                    <label class="space-y-2"
                        ><span class="text-sm font-medium">Estoque inicial</span
                        ><Input
                            v-model.number="form.stock_quantity"
                            type="number"
                            min="0"
                        /><span
                            v-if="form.errors.stock_quantity"
                            class="text-sm text-destructive"
                            >{{ form.errors.stock_quantity }}</span
                        ></label
                    >
                    <label class="flex items-center gap-2 pt-7"
                        ><input v-model="form.is_active" type="checkbox" /><span
                            class="text-sm font-medium"
                            >Peça ativa</span
                        ></label
                    >
                </div>
                <label class="block space-y-2"
                    ><span class="text-sm font-medium"
                        >Apelidos e nomes alternativos</span
                    ><textarea
                        v-model="form.aliases"
                        class="min-h-28 w-full rounded-md border bg-transparent p-3 text-sm"
                        placeholder="Um apelido por linha"
                    ></textarea
                    ><span
                        v-if="form.errors.aliases"
                        class="text-sm text-destructive"
                        >{{ form.errors.aliases }}</span
                    ></label
                >
                <div class="flex justify-end gap-3">
                    <Button variant="outline" as-child
                        ><Link :href="partRoutes.index()"
                            >Cancelar</Link
                        ></Button
                    ><Button :disabled="form.processing" type="submit">{{
                        part ? 'Salvar alterações' : 'Cadastrar peça'
                    }}</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
