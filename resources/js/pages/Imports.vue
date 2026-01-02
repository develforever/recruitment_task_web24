<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { imports } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue'
import { formatDate } from '@/helpers/date'
import axios from 'axios'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Imports',
        href: imports().url,
    },
];

const rows = ref([])
const loading = ref(true)
const error = ref(null)
const page = ref(1);

const fetchImports = async () => {
  loading.value = true
  error.value = null
  try {
    
    const { data } = await axios.get(`/api/imports?page=${page.value}`)
    rows.value = data
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Błąd pobierania importów'
    if (e.response?.status === 401) {
      window.location.href = '/login'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
 fetchImports()
})

watch(page, () => {
  fetchImports()
})
</script>

<template>
  <Head title="Imports" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
    >
      <h2 class="mb-4 text-lg font-bold">Lista importów</h2>

      <div v-if="loading">Loading...</div>
      <div v-if="error" class="text-red-500">Error: {{ error }}</div>

      <table class="min-w-full border">
        <thead class="divide-y border-t border-gray-200 dark:border-gray-700">
          <tr>
            <th>#</th>
            <th>ID</th>
            <th>Plik</th>
            <th>Records(s/f)</th>
            <th>Status</th>
            <th>Data importu</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody class="divide-y border-t border-gray-200 dark:border-gray-700">
          <tr class="even:bg-gray-200/10" v-for="(imp, index) in rows.data" :key="imp.id">
            <td>{{ index + 1 + (page - 1) * rows.per_page }}</td>
            <td>{{ imp.id }}</td>
            <td>{{ imp.file_name }}</td>
            <td>{{ imp.successful_records }} / {{ imp.failed_records }}</td>
            <td>{{ imp.status }}</td>
            <td>{{ formatDate(imp.created_at, { dateStyle: "medium" }) }}</td>
            <td>
              <Link
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold inline-block px-4 rounded text-sm"
                :href="`imports/${imp.id}`"
                >View</Link
              >
            </td>
          </tr>
        </tbody>
        <tfoot class="divide-y border-t border-gray-200 dark:border-gray-700">
          <tr colspan="6">
            <p>
              Total Imports: {{ rows.total }}<br />
              <button class="mr-2" v-if="rows.prev_page_url" @click="page--" :disabled="loading">Prev</button>
              <button class="mx-2">{{ page }}</button>
              <button class="ml-2" v-if="rows.next_page_url" @click="page++" :disabled="loading">Next</button>
            </p>
          </tr>
        </tfoot>
      </table>
    </div>
  </AppLayout>
</template>
