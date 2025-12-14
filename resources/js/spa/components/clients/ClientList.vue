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
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">
      
      <h1 class="text-xl text-gray-900 font-bold">
        Clients
      </h1>

      <!-- Search -->
      <div>
        <SearchInput v-model="search" />
      </div>

      <router-link
        :to="{ name: 'client-create' }"
        class="fixed right-4 bottom-4 inline-flex items-center gap-2 pr-4 pl-3 py-2 bg-black text-white text-md rounded-xs hover:bg-gray-800 transition-colors">
        <PhPlus class="w-5 h-5" />
        Add Client
      </router-link>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredClients.length === 0" class="text-center py-16">
      <div class="text-gray-400 mb-2">No clients found</div>
      <p class="text-sm text-gray-400">Create your first client to get started</p>
    </div>

    <!-- Clients List -->
    <div v-else class="overflow-hidden border-t border-gray-100">
      <ul class="divide-y divide-gray-100">
        <li
          v-for="client in filteredClients"
          :key="client.id"
          :class="[
            'flex items-center justify-between py-4 hover:bg-gray-50/50 transition-colors',
            !client.publish && 'opacity-50'
          ]"
        >
          <div class="flex items-center gap-x-8">
            <span v-if="client.acronym" class="font-bold">{{ client.acronym }}</span>
            <div>
              {{ client.name }}<span v-if="client.city">, {{ client.city }}</span>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <router-link
              :to="{ name: 'contacts', params: { clientId: client.id } }"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Contacts"
            >
              <PhUsers class="w-5 h-5" />
            </router-link>
            <button
              @click="toggleStatus(client)"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              :title="client.publish ? 'Hide' : 'Show'"
            >
              <PhEye v-if="client.publish" class="w-5 h-5" />
              <PhEyeSlash v-else class="w-5 h-5" />
            </button>
            <router-link
              :to="{ name: 'client-edit', params: { id: client.id } }"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Edit"
            >
              <PhPencil class="w-5 h-5" />
            </router-link>
            <button
              @click="cloneClient(client.id)"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Clone"
            >
              <PhCopy class="w-5 h-5" />
            </button>
            <button
              @click="confirmDelete(client.id)"
              class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer rounded-sm transition-colors"
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
