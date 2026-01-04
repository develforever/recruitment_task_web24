<script setup lang="ts">
import { formatDate } from '@/helpers/date';
import AppLayout from '@/layouts/AppLayout.vue';
import { imports } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Imports',
        href: imports().url,
    },
];

const rows = ref([]);
const loading = ref(true);
const error = ref(null);
const page = ref(1);

const fetchImports = async () => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get(`/api/imports?page=${page.value}`);
        rows.value = data.data || null;
    } catch (e) {
        error.value =
            e.response?.data?.message ||
            e.message ||
            'Błąd pobierania importów';
        if (e.response?.status === 401) {
            window.location.href = '/login';
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchImports();
});

watch(page, () => {
    fetchImports();
});
</script>

<template>
    <Head title="Importy" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="mb-1 text-3xl font-bold text-foreground">
                        Historia importów
                    </h1>
                    <p class="text-muted-foreground">
                        Zarządzaj i monitoruj swoje importy transakcji bankowych
                    </p>
                </div>
                <Link href="/upload" class="btn-primary"> Nowy import </Link>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="form-wrapper">
                <div class="flex items-center justify-center py-12">
                    <div class="flex flex-col items-center gap-4">
                        <svg
                            class="h-8 w-8 animate-spin text-primary"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <p class="text-muted-foreground">
                            Wczytywanie importów...
                        </p>
                    </div>
                </div>
            </div>

            <!-- Error state -->
            <div
                v-else-if="error"
                class="form-error rounded-lg border border-destructive/30 bg-destructive/10 p-4"
            >
                <p class="font-semibold text-destructive">
                    Błąd pobierania importów
                </p>
                <p class="mt-1 text-sm text-destructive/80">{{ error }}</p>
            </div>

            <!-- Empty state -->
            <div
                v-else-if="!rows.data || rows.data.length === 0"
                class="form-wrapper py-12 text-center"
            >
                <div class="space-y-4">
                    <div class="text-5xl">📁</div>
                    <p class="font-semibold text-foreground">Brak importów</p>
                    <p class="mb-4 text-muted-foreground">
                        Zacznij od dodania pierwszego importu transakcji
                        bankowych
                    </p>
                    <Link href="/imports/upload" class="btn-primary">
                        Utwórz nowy import
                    </Link>
                </div>
            </div>

            <!-- Table -->
            <div v-else class="form-wrapper overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-border bg-secondary/50">
                            <tr
                                class="text-left text-sm font-semibold text-foreground"
                            >
                                <th class="px-6 py-4">Nazwa pliku</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Rekordy</th>
                                <th class="px-6 py-4">Data</th>
                                <th class="px-6 py-4 text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="imp in rows.data"
                                :key="imp.id"
                                class="transition-colors hover:bg-secondary/30"
                            >
                                <!-- File name -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-lg text-primary"
                                        >
                                            <span
                                                v-if="
                                                    imp.file_name.endsWith(
                                                        '.csv',
                                                    )
                                                "
                                                >📄</span
                                            >
                                            <span
                                                v-else-if="
                                                    imp.file_name.endsWith(
                                                        '.json',
                                                    )
                                                "
                                                >{ }</span
                                            >
                                            <span
                                                v-else-if="
                                                    imp.file_name.endsWith(
                                                        '.xml',
                                                    )
                                                "
                                                >&lt; &gt;</span
                                            >
                                            <span v-else>📎</span>
                                        </div>
                                        <div>
                                            <p
                                                class="max-w-xs truncate font-medium text-foreground"
                                            >
                                                {{ imp.file_name }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                ID: {{ imp.id }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <span
                                        v-if="imp.status === 'completed'"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800 dark:bg-green-950 dark:text-green-400"
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full bg-green-600 dark:bg-green-400"
                                        ></span>
                                        Zakończone
                                    </span>
                                    <span
                                        v-else-if="imp.status === 'processing'"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 dark:bg-blue-950 dark:text-blue-400"
                                    >
                                        <span
                                            class="h-2 w-2 animate-spin rounded-full bg-blue-600 dark:bg-blue-400"
                                        ></span>
                                        Przetwarzanie
                                    </span>
                                    <span
                                        v-else-if="imp.status === 'failed'"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-destructive/20 px-3 py-1 text-xs font-medium text-destructive"
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full bg-destructive"
                                        ></span>
                                        Błąd
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-full bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground"
                                    >
                                        {{ imp.status }}
                                    </span>
                                </td>

                                <!-- Records -->
                                <td class="px-6 py-4 text-center">
                                    <div
                                        class="flex items-center justify-center gap-4"
                                    >
                                        <div class="text-center">
                                            <p
                                                class="text-lg font-bold text-green-600 dark:text-green-400"
                                            >
                                                {{ imp.successful_records }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Sukces
                                            </p>
                                        </div>
                                        <div class="text-border">/</div>
                                        <div class="text-center">
                                            <p
                                                class="text-lg font-bold text-destructive"
                                            >
                                                {{ imp.failed_records }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Błędy
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Date -->
                                <td
                                    class="px-6 py-4 text-sm text-muted-foreground"
                                >
                                    {{
                                        formatDate(imp.created_at, {
                                            dateStyle: 'medium',
                                        })
                                    }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="`imports/${imp.id}`"
                                        class="btn-secondary text-xs"
                                    >
                                        Szczegóły
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    class="flex items-center justify-between border-t border-border bg-secondary/30 px-6 py-4"
                >
                    <div class="text-sm text-muted-foreground">
                        Razem:
                        <span class="font-semibold text-foreground">{{
                            rows.total
                        }}</span>
                        importów
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="rows.prev_page_url"
                            @click="page--"
                            :disabled="loading"
                            class="btn-secondary text-xs"
                        >
                            ← Poprzednia
                        </button>

                        <div
                            class="flex items-center gap-2 rounded-md bg-secondary px-3 py-1.5"
                        >
                            <span class="text-sm text-foreground">
                                Strona
                                <span class="font-semibold">{{ page }}</span> z
                                <span class="font-semibold">{{
                                    rows.last_page
                                }}</span>
                            </span>
                        </div>

                        <button
                            v-if="rows.next_page_url"
                            @click="page++"
                            :disabled="loading"
                            class="btn-secondary text-xs"
                        >
                            Następna →
                        </button>
                    </div>

                    <div class="text-sm text-muted-foreground">
                        {{ rows.per_page }} na stronę
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
