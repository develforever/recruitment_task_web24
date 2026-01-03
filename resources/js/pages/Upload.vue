<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { imports } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Imports',
        href: imports().url,
    },
];


const form = useForm({
    name: "Data file import (csv,json,xml)",
    file: null,
})

const result = ref(null);

const submit = async () => {
  try {

    const data = new FormData()
    data.append('file', form.file)

    const response = await axios.post(
      '/api/imports',data, {
      headers: { 'Accept': 'application/json' },
      onUploadProgress: (event) => {
        if (event.total) {
          form.progress = { percentage: Math.round((event.loaded * 100) / event.total) }
        }
      },
    }
    )

    console.log('API response:', response.data);
    result.value = response.data?.data ?? null;
    form.reset()

    

  } catch (error) {
    if (error.response?.status === 422) {
      form.setErrors(error.response.data.errors)
    }
  }
}

const refresh = async (event) => {

  event.preventDefault();

  if (!result.value) return;

  try {
    const response = await axios.get(
      `/api/imports/${result.value.id}`,
      {
        headers: {
          'Accept': 'application/json',
        }
      }
    )

    console.log('API response:', response.data);
    result.value = response.data?.data ?? null;

  } catch (error) {
    console.error('Error fetching import status:', error);
  }
}


</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
    >
      <form @submit.prevent="submit">
        <input type="text" v-model="form.name" />
        <input
          type="file"
          required
          @input="
            form.file = $event.target.files[0];
            form.name = $event.target.files[0].name;
          "
        />
        <progress v-if="form.progress" :value="form.progress.percentage" max="100">
          {{ form.progress.percentage }}%
        </progress>
        <button
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold inline-block px-4 rounded text-sm"
          :disabled="form.processing || !form.file"
        >
          Importuj
        </button>

        <div v-if="form.errors.file">{{ form.errors.file }}</div>

        <div v-if="result">
          <p>
            Status: {{ result.status }}<br />
            Success/failed: {{ result.successful_records }} / {{ result.failed_records
            }}<br />
            Total: {{ result.total_records }}<br />
            <Link
              v-if="result.status !== 'processing'"
              class="bg-blue-500 hover:bg-blue-700 text-white font-bold inline-block px-4 rounded text-sm"
              :href="`/imports/${result.id}`"
              >Show log</Link
            >
            <button type="button" v-else @click="refresh">Click to refresh</button>
          </p>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
