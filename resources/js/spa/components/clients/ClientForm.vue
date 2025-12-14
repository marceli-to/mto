<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  clientId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['saved', 'cancel'])

const { get, post } = useApi()
const { success, error } = useToast()

const isEdit = computed(() => !!props.clientId)
const title = computed(() => isEdit.value ? 'Edit Client' : 'New Client')

const loading = ref(false)
const saving = ref(false)
const errors = ref({})
const originalAcronym = ref(null)

const client = ref({
  name: '',
  acronym: '',
  byline: '',
  street: '',
  zip: '',
  city: ''
})

async function fetchClient() {
  if (!isEdit.value) return
  loading.value = true
  try {
    const data = await get(`/api/client/edit/${props.clientId}`)
    client.value = data
    originalAcronym.value = data.acronym
  } catch (e) {
    error('Failed to load client')
    emit('cancel')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  client.value = {
    name: '',
    acronym: '',
    byline: '',
    street: '',
    zip: '',
    city: ''
  }
  errors.value = {}
  originalAcronym.value = null
}

watch(() => props.clientId, (newId) => {
  if (newId) {
    fetchClient()
  } else {
    resetForm()
  }
})

async function checkAcronym() {
  const acronym = client.value.acronym
  if (!acronym || acronym === originalAcronym.value) {
    errors.value.acronym = null
    return
  }
  try {
    const data = await get(`/api/client/unique/acronym/${acronym}`)
    if (data.exists) {
      errors.value.acronym = 'Acronym already exists'
    } else {
      errors.value.acronym = null
    }
  } catch (e) {
    // Ignore
  }
}

function validate() {
  errors.value = {}
  if (!client.value.name?.trim()) {
    errors.value.name = 'Name is required'
  }
  if (errors.value.acronym) {
    // Already has acronym error
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
    let savedClient
    if (isEdit.value) {
      savedClient = await post(`/api/client/update/${props.clientId}`, client.value)
      success('Client updated')
    } else {
      savedClient = await post('/api/client/create', client.value)
      success('Client created')
    }
    emit('saved', savedClient)
  } catch (e) {
    error('Failed to save client')
  } finally {
    saving.value = false
  }
}

onMounted(fetchClient)
</script>

<template>
  <div>
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit">
      <div class="space-y-4">
        <BaseInput
          v-model="client.name"
          label="Name"
          required
          :error="errors.name"
          @focus="errors.name = null"
        />

        <BaseInput
          v-model="client.acronym"
          label="Acronym"
          placeholder="3 characters"
          :error="errors.acronym"
          @blur="checkAcronym"
        />

        <BaseInput
          v-model="client.byline"
          label="Byline"
        />

        <BaseInput
          v-model="client.street"
          label="Street, No."
        />

        <BaseInput
          v-model="client.zip"
          label="ZIP"
        />
        <BaseInput
          v-model="client.city"
          label="City"
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
