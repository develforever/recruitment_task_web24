<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { imports } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, defineProps } from 'vue';
import { formatDate } from '@/helpers/date'

const props = defineProps({
  token: String,
  importId: String
});

const logs = ref([])
const loading = ref(true)
const error = ref(null)

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Imports',
        href: imports().url,
    },
    {
        title: `Import Details of #${props.importId}`,
        href: "#",
    },
];





onMounted(async () => {
  try {
    const response = await fetch(`/api/imports/${props.importId}`, {
      credentials: 'include',
      headers: {
        'Authorization': `Bearer ${props.token}`,
        'Accept': 'application/json'
      }
    })
    if (!response.ok) throw new Error('Błąd pobierania importów')
    const result = await response.json()
    logs.value = result.data?.data || result.data || null;

  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
    >
      <p class="mb-4 text-lg font-bold">
        File: {{ logs.file_name }}<br />
        Status: {{ logs.status }}<br />
        Successful/Failed: {{ logs.successful_records }} / {{ logs.failed_records }}<br />
        Total: {{ logs.total_records }}<br />
        Created at: {{ formatDate(logs.created_at, { dateStyle: "medium" }) }}<br />
      </p>

      <Link
        class="bg-blue-500 hover:bg-blue-700 text-white font-bold inline-block px-4 rounded text-sm"
        :href="`/imports`"
        >Back to Imports</Link
      ><br />
      <div v-if="loading">Loading...</div>
      <div v-if="error" class="text-red-500">Error: {{ error }}</div>

      <table class="min-w-full border">
        <thead class="divide-y border-t border-gray-200 dark:border-gray-700">
          <tr>
            <th>Transaction ID</th>
            <th>Error Message</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody class="divide-y border-t border-gray-200 dark:border-gray-700">
          <tr class="even:bg-gray-200/10" v-for="log in logs.logs" :key="log.id">
            <td>{{ log.transaction_id }}</td>
            <td>{{ log.error_message }}</td>
            <td>{{ log.created_at }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
