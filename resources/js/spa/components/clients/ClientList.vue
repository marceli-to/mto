<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCopy, PhEye, PhEyeSlash, PhUsers } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import Flyout from '@/components/ui/Flyout.vue'
import ClientForm from './ClientForm.vue'
import ContactForm from '../contacts/ContactForm.vue'

const { get, del } = useApi()
const { success, error } = useToast()

const clients = ref([])
const search = ref('')
const loading = ref(true)
const deleteDialog = ref({ show: false, id: null, loading: false })
const contactDeleteDialog = ref({ show: false, id: null, clientId: null, loading: false })
const flyout = ref({ show: false, clientId: null })
const contactsFlyout = ref({ show: false, clientId: null, clientName: '', contacts: [], loading: false })
const contactFormFlyout = ref({ show: false, contactId: null, clientId: null })

const flyoutTitle = computed(() => flyout.value.clientId ? 'Edit Client' : 'New Client')
const contactFormTitle = computed(() => contactFormFlyout.value.contactId ? 'Edit Contact' : 'New Contact')

function openCreate() {
  flyout.value = { show: true, clientId: null }
}

function openEdit(id) {
  flyout.value = { show: true, clientId: id }
}

function closeFlyout() {
  flyout.value = { show: false, clientId: null }
}

function onClientSaved(savedClient) {
  if (flyout.value.clientId) {
    // Update existing client in list
    const index = clients.value.findIndex(c => c.id === flyout.value.clientId)
    if (index !== -1) {
      clients.value[index] = { ...clients.value[index], ...savedClient }
    }
  } else {
    // Add new client to list
    clients.value.unshift(savedClient)
  }
  closeFlyout()
}

// Contacts flyout functions
async function openContacts(client) {
  contactsFlyout.value = { show: true, clientId: client.id, clientName: client.name, contacts: [], loading: true }
  try {
    const data = await get(`/api/contacts/get/${client.id}`)
    contactsFlyout.value.contacts = data.data || []
  } catch (e) {
    error('Failed to load contacts')
  } finally {
    contactsFlyout.value.loading = false
  }
}

function closeContactsFlyout() {
  contactsFlyout.value = { show: false, clientId: null, clientName: '', contacts: [], loading: false }
}

function openContactCreate() {
  contactFormFlyout.value = { show: true, contactId: null, clientId: contactsFlyout.value.clientId }
}

function openContactEdit(contactId) {
  contactFormFlyout.value = { show: true, contactId, clientId: contactsFlyout.value.clientId }
}

function closeContactFormFlyout() {
  contactFormFlyout.value = { show: false, contactId: null, clientId: null }
}

function onContactSaved(savedContact) {
  if (contactFormFlyout.value.contactId) {
    const index = contactsFlyout.value.contacts.findIndex(c => c.id === contactFormFlyout.value.contactId)
    if (index !== -1) {
      contactsFlyout.value.contacts[index] = { ...contactsFlyout.value.contacts[index], ...savedContact }
    }
  } else {
    contactsFlyout.value.contacts.unshift(savedContact)
  }
  closeContactFormFlyout()
}

function confirmDeleteContact(id) {
  contactDeleteDialog.value = { show: true, id, clientId: contactsFlyout.value.clientId, loading: false }
}

async function deleteContact() {
  contactDeleteDialog.value.loading = true
  try {
    await del(`/api/contact/destroy/${contactDeleteDialog.value.id}`)
    contactsFlyout.value.contacts = contactsFlyout.value.contacts.filter(c => c.id !== contactDeleteDialog.value.id)
    success('Contact deleted')
    contactDeleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete contact')
  } finally {
    contactDeleteDialog.value.loading = false
  }
}

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

      <button
        v-if="!flyout.show"
        @click="openCreate"
        class="fixed right-4 bottom-4 z-20 inline-flex items-center gap-2 pr-4 pl-3 py-2 bg-black text-white text-md rounded-xs hover:bg-gray-800 transition-colors cursor-pointer">
        <PhPlus class="w-5 h-5" />
        Add Client
      </button>
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
            <button
              @click="openContacts(client)"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Contacts"
            >
              <PhUsers class="w-5 h-5" />
            </button>
            <button
              @click="toggleStatus(client)"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              :title="client.publish ? 'Hide' : 'Show'"
            >
              <PhEye v-if="client.publish" class="w-5 h-5" />
              <PhEyeSlash v-else class="w-5 h-5" />
            </button>
            <button
              @click="openEdit(client.id)"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Edit"
            >
              <PhPencil class="w-5 h-5" />
            </button>
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

    <Flyout
      :show="flyout.show"
      :title="flyoutTitle"
      size="md"
      @close="closeFlyout"
    >
      <ClientForm
        :client-id="flyout.clientId"
        @saved="onClientSaved"
        @cancel="closeFlyout"
      />
    </Flyout>

    <!-- Contacts Flyout -->
    <Flyout
      :show="contactsFlyout.show"
      :title="`Contacts - ${contactsFlyout.clientName}`"
      size="md"
      @close="closeContactsFlyout"
    >
      <div v-if="contactsFlyout.loading" class="text-center py-12 text-gray-500">
        Loading...
      </div>
      <div v-else>
        <div class="flex items-center justify-between mb-4">
          <span class="text-gray-500">{{ contactsFlyout.contacts.length }} contact(s)</span>
          <button
            @click="openContactCreate"
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
            title="Add Contact"
          >
            <PhPlus class="w-5 h-5" />
          </button>
        </div>
        <div v-if="contactsFlyout.contacts.length === 0" class="text-center py-8 text-gray-500">
          No contacts yet
        </div>
        <ul v-else class="divide-y divide-gray-100">
          <li
            v-for="contact in contactsFlyout.contacts"
            :key="contact.id"
            class="flex items-center justify-between py-3"
          >
            <div>
              <p class="text-gray-900">{{ contact.firstname }} {{ contact.name }}</p>
              <p v-if="contact.email" class="text-gray-500">{{ contact.email }}</p>
            </div>
            <div class="flex items-center gap-1">
              <button
                @click="openContactEdit(contact.id)"
                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                title="Edit"
              >
                <PhPencil class="w-4 h-4" />
              </button>
              <button
                @click="confirmDeleteContact(contact.id)"
                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer rounded-sm transition-colors"
                title="Delete"
              >
                <PhTrash class="w-4 h-4" />
              </button>
            </div>
          </li>
        </ul>
      </div>
    </Flyout>

    <!-- Contact Form Flyout -->
    <Flyout
      :show="contactFormFlyout.show"
      :title="contactFormTitle"
      size="md"
      @close="closeContactFormFlyout"
    >
      <ContactForm
        :contact-id="contactFormFlyout.contactId"
        :client-id="contactFormFlyout.clientId"
        @saved="onContactSaved"
        @cancel="closeContactFormFlyout"
      />
    </Flyout>

    <!-- Contact Delete Dialog -->
    <ConfirmDialog
      :show="contactDeleteDialog.show"
      title="Delete Contact"
      message="Are you sure you want to delete this contact?"
      :loading="contactDeleteDialog.loading"
      @confirm="deleteContact"
      @cancel="contactDeleteDialog.show = false"
    />
  </div>
</template>
