<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  contactId: {
    type: [Number, String],
    default: null
  },
  clientId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['saved', 'cancel'])

const { get, post } = useApi()
const { success, error } = useToast()

const isEdit = computed(() => !!props.contactId)

const loading = ref(false)
const saving = ref(false)
const errors = ref({})

const contact = ref({
  name: '',
  firstname: '',
  email: '',
  phone: '',
  client_id: null
})

async function fetchContact() {
  if (!isEdit.value) {
    contact.value.client_id = props.clientId
    return
  }
  loading.value = true
  try {
    const data = await get(`/api/contact/edit/${props.contactId}`)
    contact.value = data
  } catch (e) {
    error('Failed to load contact')
    emit('cancel')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  contact.value = {
    name: '',
    firstname: '',
    email: '',
    phone: '',
    client_id: props.clientId
  }
  errors.value = {}
}

watch(() => props.contactId, (newId) => {
  if (newId) {
    fetchContact()
  } else {
    resetForm()
  }
})

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
    let savedContact
    if (isEdit.value) {
      savedContact = await post(`/api/contact/update/${props.contactId}`, contact.value)
      success('Contact updated')
    } else {
      savedContact = await post('/api/contact/create', contact.value)
      success('Contact created')
    }
    emit('saved', savedContact)
  } catch (e) {
    error('Failed to save contact')
  } finally {
    saving.value = false
  }
}

onMounted(fetchContact)
</script>

<template>
  <div>
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit">
      <div class="space-y-4">
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

      <div class="flex items-center justify-end gap-3 mt-8">
        <BaseButton type="button" variant="secondary" @click="emit('cancel')">
          Cancel
        </BaseButton>
        <BaseButton type="submit" :loading="saving">
          {{ isEdit ? 'Update' : 'Create' }}
        </BaseButton>
      </div>
    </form>
  </div>
</template>
