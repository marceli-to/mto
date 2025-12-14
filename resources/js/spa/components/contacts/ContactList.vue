<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { PhPlus, PhPencil, PhTrash, PhArrowLeft } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const route = useRoute()
const { get, del } = useApi()
const { success, error } = useToast()

const contacts = ref([])
const loading = ref(true)
const deleteDialog = ref({ show: false, id: null, loading: false })

const clientId = route.params.clientId

async function fetchContacts() {
  loading.value = true
  try {
    const data = await get(`/api/contacts/get/${clientId}`)
    contacts.value = data.data || []
  } catch (e) {
    error('Failed to load contacts')
  } finally {
    loading.value = false
  }
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteContact() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/contact/destroy/${deleteDialog.value.id}`)
    contacts.value = contacts.value.filter(c => c.id !== deleteDialog.value.id)
    success('Contact deleted')
    deleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete contact')
  } finally {
    deleteDialog.value.loading = false
  }
}

onMounted(fetchContacts)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <router-link
          :to="{ name: 'clients' }"
          class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded"
        >
          <PhArrowLeft class="w-5 h-5" />
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">Contacts</h1>
      </div>
      <router-link
        :to="{ name: 'contact-create', params: { clientId } }"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
      >
        <PhPlus class="w-5 h-5" />
        Add Contact
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <div v-else-if="contacts.length === 0" class="text-center py-12 text-gray-500">
      No contacts found
    </div>

    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <ul class="divide-y divide-gray-200">
        <li
          v-for="contact in contacts"
          :key="contact.id"
          class="flex items-center justify-between px-6 py-4 hover:bg-gray-50"
        >
          <div>
            <p class="font-medium text-gray-900">{{ contact.firstname }} {{ contact.name }}</p>
            <p v-if="contact.email" class="text-sm text-gray-500">{{ contact.email }}</p>
          </div>
          <div class="flex items-center gap-2">
            <router-link
              :to="{ name: 'contact-edit', params: { id: contact.id } }"
              class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded"
              title="Edit"
            >
              <PhPencil class="w-5 h-5" />
            </router-link>
            <button
              @click="confirmDelete(contact.id)"
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
      title="Delete Contact"
      message="Are you sure you want to delete this contact?"
      :loading="deleteDialog.loading"
      @confirm="deleteContact"
      @cancel="deleteDialog.show = false"
    />
  </div>
</template>
