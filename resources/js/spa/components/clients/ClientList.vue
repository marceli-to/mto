<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCopy, PhEye, PhEyeSlash, PhUsers } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const { get, del } = useApi()
const { success, error } = useToast()

const clients = ref([])
const search = ref('')
const loading = ref(true)
const deleteDialog = ref({ show: false, id: null, loading: false })

const filteredClients = computed(() => {
  if (!search.value) return clients.value
  const q = search.value.toLowerCase()
  return clients.value.filter(c =>
    c.name?.toLowerCase().includes(q) ||
    c.city?.toLowerCase().includes(q)
  )
})

async function fetchClients() {
  loading.value = true
  try {
    const data = await get('/api/clients/get')
    clients.value = data.data || []
  } catch (e) {
    error('Failed to load clients')
  } finally {
    loading.value = false
  }
}

async function toggleStatus(client) {
  try {
    const data = await get(`/api/client/status/${client.id}`)
    client.publish = data
    success('Status updated')
  } catch (e) {
    error('Failed to update status')
  }
}

async function cloneClient(id) {
  try {
    const data = await get(`/api/client/duplicate/${id}`)
    clients.value.unshift(data)
    success('Client cloned')
  } catch (e) {
    error('Failed to clone client')
  }
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteClient() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/client/destroy/${deleteDialog.value.id}`)
    clients.value = clients.value.filter(c => c.id !== deleteDialog.value.id)
    success('Client deleted')
    deleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete client')
  } finally {
    deleteDialog.value.loading = false
  }
}

onMounted(fetchClients)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
      <router-link
        :to="{ name: 'client-create' }"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
      >
        <PhPlus class="w-5 h-5" />
        Add Client
      </router-link>
    </div>

    <div class="mb-6">
      <SearchInput v-model="search" placeholder="Search by name or city..." />
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <div v-else-if="filteredClients.length === 0" class="text-center py-12 text-gray-500">
      No clients found
    </div>

    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <ul class="divide-y divide-gray-200">
        <li
          v-for="client in filteredClients"
          :key="client.id"
          :class="[
            'flex items-center justify-between px-6 py-4 hover:bg-gray-50',
            !client.publish && 'opacity-50'
          ]"
        >
          <div>
            <p class="font-medium text-gray-900">{{ client.name }}</p>
            <p v-if="client.city" class="text-sm text-gray-500">{{ client.city }}</p>
          </div>
          <div class="flex items-center gap-2">
            <router-link
              :to="{ name: 'contacts', params: { clientId: client.id } }"
              class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded"
              title="Contacts"
            >
              <PhUsers class="w-5 h-5" />
            </router-link>
            <button
              @click="toggleStatus(client)"
              class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded"
              :title="client.publish ? 'Hide' : 'Show'"
            >
              <PhEye v-if="client.publish" class="w-5 h-5" />
              <PhEyeSlash v-else class="w-5 h-5" />
            </button>
            <router-link
              :to="{ name: 'client-edit', params: { id: client.id } }"
              class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded"
              title="Edit"
            >
              <PhPencil class="w-5 h-5" />
            </router-link>
            <button
              @click="cloneClient(client.id)"
              class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded"
              title="Clone"
            >
              <PhCopy class="w-5 h-5" />
            </button>
            <button
              @click="confirmDelete(client.id)"
              class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded"
              title="Delete"
            >
              <PhTrash class="w-5 h-5" />
            </button>
          </div>
        </li>
      </ul>
    </div>

    <ConfirmDialog
      :show="deleteDialog.show"
      title="Delete Client"
      message="Are you sure you want to delete this client? This action cannot be undone."
      :loading="deleteDialog.loading"
      @confirm="deleteClient"
      @cancel="deleteDialog.show = false"
    />
  </div>
</template>
