<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import FormActions from '@/components/ui/FormActions.vue'

const route = useRoute()
const router = useRouter()
const { get, post } = useApi()
const { success, error } = useToast()

const isEdit = computed(() => route.name === 'contact-edit')
const title = computed(() => isEdit.value ? 'Edit Contact' : 'New Contact')

const loading = ref(false)
const saving = ref(false)
const errors = ref({})
const clientId = ref(route.params.clientId || null)

const contact = ref({
  name: '',
  firstname: '',
  email: '',
  phone: '',
  client_id: null
})

async function fetchContact() {
  if (!isEdit.value) {
    contact.value.client_id = route.params.clientId
    return
  }
  loading.value = true
  try {
    const data = await get(`/api/contact/edit/${route.params.id}`)
    contact.value = data
    clientId.value = data.client_id
  } catch (e) {
    error('Failed to load contact')
    router.back()
  } finally {
    loading.value = false
  }
}

function validate() {
  errors.value = {}
  if (!contact.value.name?.trim()) {
    errors.value.name = 'Name is required'
  }
  if (!contact.value.firstname?.trim()) {
    errors.value.firstname = 'First name is required'
  }
  return Object.keys(errors.value).length === 0
}

async function submit() {
  if (!validate()) {
    error('Please fix the errors')
    return
  }

  saving.value = true
  try {
    if (isEdit.value) {
      await post(`/api/contact/update/${route.params.id}`, contact.value)
      success('Contact updated')
    } else {
      await post('/api/contact/create', contact.value)
      success('Contact created')
    }
    router.push({ name: 'contacts', params: { clientId: clientId.value } })
  } catch (e) {
    error('Failed to save contact')
  } finally {
    saving.value = false
  }
}

const backRoute = computed(() => {
  if (clientId.value) {
    return { name: 'contacts', params: { clientId: clientId.value } }
  }
  return { name: 'clients' }
})

onMounted(fetchContact)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ title }}</h1>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit" class="bg-white rounded-lg shadow p-6 max-w-2xl">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <BaseInput
            v-model="contact.firstname"
            label="First Name"
            required
            :error="errors.firstname"
            @focus="errors.firstname = null"
          />
          <BaseInput
            v-model="contact.name"
            label="Last Name"
            required
            :error="errors.name"
            @focus="errors.name = null"
          />
        </div>

        <BaseInput
          v-model="contact.email"
          label="Email"
          type="email"
        />

        <BaseInput
          v-model="contact.phone"
          label="Phone"
          type="tel"
        />
      </div>

      <FormActions
        :loading="saving"
        :back-route="backRoute"
      />
    </form>
  </div>
</template>
