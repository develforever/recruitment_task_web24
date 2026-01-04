<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { imports } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Imports',
        href: imports().url,
    },
];

const form = useForm({
    name: 'Data file import (csv,json,xml)',
    file: null,
});

interface ImportResult {
    id: number;
    status: string;
    successful_records: number;
    failed_records: number;
    total_records: number;
}

const result = ref<ImportResult | null>(null);
const uploadProgress = ref(0);

const submit = async () => {
    try {
        const data = new FormData();
        data.append('file', form.file);

        const response = await axios.post('/api/imports', data, {
            headers: { Accept: 'application/json' },
            onUploadProgress: (event) => {
                if (event.total) {
                    uploadProgress.value = Math.round(
                        (event.loaded * 100) / event.total,
                    );
                }
            },
        });

        result.value = response.data?.data ?? null;
        form.reset();
        uploadProgress.value = 0;
        form.file = null;

        refresh();
    } catch (error: Error | any) {
        uploadProgress.value = 0;
        if (error.response?.status === 422) {
            form.setErrors(error.response.data.errors);
        } else if (
            error.response?.status === 401 ||
            error.response?.status === 419
        ) {
            window.location.href = '/login';
        } else {
            form.setErrors({
                file:
                    error.response?.data?.message ||
                    'Błąd podczas przesyłania pliku',
            });
        }
    }
};

const refresh = async (event?: Event) => {
    if (event) {
        event.preventDefault();
    }

    if (!result.value) return;

    try {
        const response = await axios.get(`/api/imports/${result.value.id}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        result.value = response.data?.data ?? null;
    } catch (error) {
        if (error.response?.status === 401 || error.response?.status === 419) {
            window.location.href = '/login';
        }
    }
};

interface ImportProgressEvent {
    current_record: number;
    failed_records: number;
    import_id: number;
    last_error: string;
    percentage: number;
    status: string;
    successful_records: number;
    total_records: number;
}

useEcho<ImportProgressEvent>(`import-progress`, '.progress-updated', (e) => {
    if (!result.value || e.import_id !== result.value.id) {
        return;
    }
    result.value.status = e.status;
    result.value.successful_records = e.successful_records;
    result.value.failed_records = e.failed_records;
    result.value.total_records = e.total_records;
    uploadProgress.value = e.percentage;
});
</script>

<template>
    <Head title="Import danych" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Upload form card -->
            <div class="form-wrapper">
                <div class="mb-6">
                    <h1 class="mb-2 text-2xl font-bold text-foreground">
                        Import transakcji bankowych
                    </h1>
                    <p class="text-muted-foreground">
                        Obsługiwane formaty: CSV, JSON, XML
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name input -->
                    <div class="form-group">
                        <label for="import-name">Nazwa importu</label>
                        <input
                            id="import-name"
                            type="text"
                            v-model="form.name"
                            placeholder="np. Import z miesiąca stycznia"
                            class="w-full"
                        />
                    </div>

                    <!-- File input -->
                    <div class="form-group">
                        <label for="import-file">Wybierz plik</label>
                        <input
                            id="import-file"
                            type="file"
                            required
                            accept=".csv,.json,.xml"
                            @input="
                                form.file = $event.target.files[0];
                                form.name = $event.target.files[0].name;
                            "
                            class="w-full cursor-pointer"
                        />
                        <p class="form-hint">Maksymalny rozmiar pliku: 10 MB</p>
                    </div>

                    <!-- Error display -->
                    <div
                        v-if="form.errors.file"
                        class="form-error rounded-md border border-destructive/30 bg-destructive/10 p-3"
                    >
                        <p class="text-sm">{{ form.errors.file }}</p>
                    </div>

                    <!-- Progress bar -->
                    <div
                        v-if="uploadProgress > 0 && uploadProgress < 100"
                        class="space-y-2"
                    >
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground"
                                >Przesyłanie pliku...</span
                            >
                            <span class="font-semibold text-primary"
                                >{{ uploadProgress }}%</span
                            >
                        </div>
                        <progress
                            :value="uploadProgress"
                            max="100"
                            class="w-full"
                        ></progress>
                    </div>

                    <!-- Submit button -->
                    <button
                        type="submit"
                        :disabled="form.processing || !form.file"
                        class="btn-primary w-full md:w-auto"
                    >
                        <span v-if="!form.processing">Importuj plik</span>
                        <span v-else class="flex items-center gap-2">
                            <svg
                                class="h-4 w-4 animate-spin"
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
                            Przetwarzanie...
                        </span>
                    </button>
                </form>
            </div>

            <!-- Results card -->
            <div v-if="result" class="form-wrapper">
                <div class="mb-4">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">
                        Wynik importu
                    </h2>

                    <!-- Status indicator -->
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="rounded-lg bg-secondary/50 p-4">
                            <p
                                class="mb-1 text-xs tracking-wider text-muted-foreground uppercase"
                            >
                                Status
                            </p>
                            <p class="text-lg font-semibold">
                                <span
                                    v-if="result.status === 'processing'"
                                    class="text-accent"
                                    >Przetwarzanie...</span
                                >
                                <span
                                    v-else-if="result.status === 'completed'"
                                    class="text-green-600 dark:text-green-400"
                                    >Zakończone</span
                                >
                                <span
                                    v-else-if="result.status === 'failed'"
                                    class="text-destructive"
                                    >Błąd</span
                                >
                                <span v-else class="text-muted-foreground">{{
                                    result.status
                                }}</span>
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950/30"
                        >
                            <p
                                class="mb-1 text-xs tracking-wider text-muted-foreground uppercase"
                            >
                                Sukces
                            </p>
                            <p
                                class="text-2xl font-bold text-green-600 dark:text-green-400"
                            >
                                {{ result.successful_records }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-destructive/30 bg-destructive/10 p-4"
                        >
                            <p
                                class="mb-1 text-xs tracking-wider text-muted-foreground uppercase"
                            >
                                Błędy
                            </p>
                            <p class="text-2xl font-bold text-destructive">
                                {{ result.failed_records }}
                            </p>
                        </div>

                        <div class="rounded-lg bg-secondary/50 p-4">
                            <p
                                class="mb-1 text-xs tracking-wider text-muted-foreground uppercase"
                            >
                                Razem
                            </p>
                            <p class="text-2xl font-bold">
                                {{ result.total_records }}
                            </p>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-if="result.status !== 'processing'"
                            class="btn-primary"
                            :href="`/imports/${result.id}`"
                        >
                            Pokaż szczegóły
                        </Link>
                        <button
                            v-else
                            type="button"
                            @click="refresh"
                            class="btn-secondary"
                        >
                            Odśwież status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
