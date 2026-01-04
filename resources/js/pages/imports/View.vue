<script setup lang="ts">
import { formatDate } from '@/helpers/date';
import AppLayout from '@/layouts/AppLayout.vue';
import { imports } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { defineProps, onMounted, ref, watch } from 'vue';

const props = defineProps({
    importId: String,
});

const importData = ref([]);
const logs = ref([]);
const loading = ref(true);
const error = ref(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Imports',
        href: imports().url,
    },
    {
        title: `Import Details of #${props.importId}`,
        href: '#',
    },
];

const page = ref(1);

const loadLogs = async () => {
    loading.value = true;
    error.value = null;
    logs.value = [];
    try {
        const { data } = await axios.get(
            `/api/imports/${props.importId}/logs?page=${page.value}`,
        );

        logs.value = data.data || null;
    } catch (e) {
        error.value =
            e.response?.data?.message ||
            e.message ||
            'Błąd pobierania logów importu';
        if (e.response?.status === 401) {
            window.location.href = '/login';
        }
    } finally {
        loading.value = false;
    }
};

const fetchImport = async () => {
    try {
        loading.value = true;
        error.value = null;
        const { data } = await axios.get(`/api/imports/${props.importId}`);

        importData.value = data?.data || null;

        loadLogs();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    fetchImport();
});

watch(page, () => {
    fetchImport();
});
</script>

<template>
    <Head title="Szczegóły importu" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header with back button -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="mb-1 text-3xl font-bold text-foreground">
                        Szczegóły importu
                    </h1>
                    <p class="text-muted-foreground">
                        Plik:
                        <span class="font-medium text-foreground">{{
                            importData.file_name
                        }}</span>
                    </p>
                </div>
                <Link href="/imports" class="btn-secondary">
                    ← Wróć do listy
                </Link>
            </div>

            <!-- Summary cards -->
            <div v-if="!loading" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <!-- Status -->
                <div class="form-wrapper">
                    <p
                        class="mb-2 text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        Status
                    </p>
                    <div class="flex items-center gap-3">
                        <div
                            class="h-3 w-3 rounded-full"
                            :class="
                                importData.status === 'completed'
                                    ? 'bg-green-600'
                                    : importData.status === 'processing'
                                      ? 'animate-pulse bg-blue-600'
                                      : importData.status === 'failed'
                                        ? 'bg-destructive'
                                        : 'bg-gray-600'
                            "
                        ></div>
                        <span class="text-xl font-bold">
                            <span
                                v-if="importData.status === 'completed'"
                                class="text-green-600 dark:text-green-400"
                                >Zakończone</span
                            >
                            <span
                                v-else-if="importData.status === 'processing'"
                                class="text-blue-600 dark:text-blue-400"
                                >Przetwarzanie</span
                            >
                            <span
                                v-else-if="importData.status === 'failed'"
                                class="text-destructive"
                                >Błąd</span
                            >
                            <span v-else class="text-muted-foreground">{{
                                importData.status
                            }}</span>
                        </span>
                    </div>
                </div>

                <!-- Successful records -->
                <div class="form-wrapper">
                    <p
                        class="mb-2 text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        Pomyślnie zaimportowane
                    </p>
                    <p
                        class="text-3xl font-bold text-green-600 dark:text-green-400"
                    >
                        {{ importData.successful_records }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">rekordy</p>
                </div>

                <!-- Failed records -->
                <div class="form-wrapper">
                    <p
                        class="mb-2 text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        Błędy
                    </p>
                    <p class="text-3xl font-bold text-destructive">
                        {{ importData.failed_records }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">rekordy</p>
                </div>

                <!-- Total -->
                <div class="form-wrapper">
                    <p
                        class="mb-2 text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        Razem
                    </p>
                    <p class="text-3xl font-bold">
                        {{ importData.total_records }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">rekordy</p>
                </div>
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
                            Wczytywanie szczegółów...
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
                    Błąd pobierania danych
                </p>
                <p class="mt-1 text-sm text-destructive/80">{{ error }}</p>
            </div>

            <!-- Logs table -->
            <div v-else class="form-wrapper">
                <h2 class="mb-4 text-lg font-semibold text-foreground">
                    Szczegółowe logi błędów
                </h2>

                <!-- Empty state -->
                <div
                    v-if="!logs.data || logs.data.length === 0"
                    class="py-12 text-center"
                >
                    <div class="space-y-2">
                        <p class="text-5xl">✓</p>
                        <p class="font-semibold text-foreground">Brak błędów</p>
                        <p class="text-muted-foreground">
                            Wszystkie rekordy zostały pomyślnie zaimportowane
                        </p>
                    </div>
                </div>

                <!-- Logs table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-border bg-secondary/50">
                            <tr
                                class="text-left text-sm font-semibold text-foreground"
                            >
                                <th class="px-6 py-4">ID transakcji</th>
                                <th class="px-6 py-4">Komunikat błędu</th>
                                <th class="px-6 py-4">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="transition-colors hover:bg-secondary/30"
                            >
                                <td class="px-6 py-4">
                                    <code
                                        class="rounded bg-secondary px-2 py-1 font-mono text-sm text-foreground"
                                        >{{ log.transaction_id }}</code
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <p
                                            class="text-sm font-medium break-words text-destructive"
                                        >
                                            {{ log.error_message }}
                                        </p>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-muted-foreground"
                                >
                                    {{
                                        formatDate(log.created_at, {
                                            dateStyle: 'medium',
                                        })
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div
                        class="flex items-center justify-between border-t border-border bg-secondary/30 px-6 py-4"
                    >
                        <div class="text-sm text-muted-foreground">
                            Razem:
                            <span class="font-semibold text-foreground">{{
                                logs.total
                            }}</span>
                            błędów
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="logs.prev_page_url"
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
                                    <span class="font-semibold">{{
                                        page
                                    }}</span>
                                    z
                                    <span class="font-semibold">{{
                                        logs.last_page
                                    }}</span>
                                </span>
                            </div>

                            <button
                                v-if="logs.next_page_url"
                                @click="page++"
                                :disabled="loading"
                                class="btn-secondary text-xs"
                            >
                                Następna →
                            </button>
                        </div>

                        <div class="text-sm text-muted-foreground">
                            {{ logs.per_page }} na stronę
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            <div
                v-if="!loading && importData.created_at"
                class="form-wrapper bg-secondary/30"
            >
                <p
                    class="mb-3 text-xs tracking-wider text-muted-foreground uppercase"
                >
                    Informacje
                </p>
                <div class="space-y-2 text-sm">
                    <p>
                        <span class="text-muted-foreground">ID importu:</span>
                        <span class="font-mono text-foreground">{{
                            importData.id
                        }}</span>
                    </p>
                    <p>
                        <span class="text-muted-foreground"
                            >Data utworzenia:</span
                        >
                        <span class="font-medium text-foreground">{{
                            formatDate(importData.created_at, {
                                dateStyle: 'full',
                                timeStyle: 'medium',
                            })
                        }}</span>
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
