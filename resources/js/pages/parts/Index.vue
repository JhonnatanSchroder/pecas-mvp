<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Power, Search, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
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

const props = defineProps<{
    parts: Part[];
    filters: { search: string };
}>();

const search = ref(props.filters.search);
let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            partRoutes.index().url,
            { search: value || undefined },
            {
                preserveState: true,
                replace: true,
            },
        );
    }, 250);
});

function formatPrice(cents: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(cents / 100);
}

function toggle(part: Part): void {
    router.patch(partRoutes.toggle(part.id).url, {}, { preserveScroll: true });
}

function remove(part: Part): void {
    if (window.confirm(`Excluir a peça “${part.name}”?`)) {
        router.delete(partRoutes.destroy(part.id).url);
    }
}
</script>

<template>
    <Head title="Peças" />

    <AppLayout :breadcrumbs="[{ title: 'Peças', href: partRoutes.index() }]">
        <div class="space-y-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">Catálogo de peças</h1>
                    <p class="text-sm text-muted-foreground">
                        Gerencie preços, estoque inicial e compatibilidades.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="partRoutes.create()">
                        <Plus class="size-4" /> Nova peça
                    </Link>
                </Button>
            </div>

            <div class="relative max-w-xl">
                <Search
                    class="absolute top-2.5 left-3 size-4 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Buscar por nome, marca, modelo, SKU ou apelido"
                />
            </div>

            <Card class="overflow-hidden p-0">
                <div
                    v-if="parts.length === 0"
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    Nenhuma peça encontrada.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b bg-muted/50 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="p-4 font-medium">Peça</th>
                                <th class="p-4 font-medium">Compatibilidade</th>
                                <th class="p-4 font-medium">Preço</th>
                                <th class="p-4 font-medium">Estoque</th>
                                <th class="p-4 font-medium">Status</th>
                                <th class="p-4 font-medium">
                                    <span class="sr-only">Ações</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="part in parts"
                                :key="part.id"
                                class="border-b last:border-0"
                            >
                                <td class="p-4">
                                    <div class="font-medium">
                                        {{ part.name }}
                                    </div>
                                    <div
                                        v-if="part.sku"
                                        class="text-xs text-muted-foreground"
                                    >
                                        SKU: {{ part.sku }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    {{ part.phone_brand }}
                                    {{ part.phone_model }}
                                </td>
                                <td class="p-4">
                                    {{ formatPrice(part.price_cents) }}
                                </td>
                                <td class="p-4">{{ part.stock_quantity }}</td>
                                <td class="p-4">
                                    <Badge
                                        :variant="
                                            part.is_active
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            part.is_active ? 'Ativa' : 'Inativa'
                                        }}
                                    </Badge>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Editar"
                                            as-child
                                        >
                                            <Link
                                                :href="partRoutes.edit(part.id)"
                                                ><Pencil class="size-4"
                                            /></Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            :title="
                                                part.is_active
                                                    ? 'Desativar'
                                                    : 'Ativar'
                                            "
                                            @click="toggle(part)"
                                        >
                                            <Power class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Excluir"
                                            @click="remove(part)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
