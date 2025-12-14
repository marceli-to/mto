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

const isEdit = computed(() => !!route.params.id)
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
    const data = await get(`/api/client/edit/${route.params.id}`)
    client.value = data
    originalAcronym.value = data.acronym
  } catch (e) {
    error('Failed to load client')
    router.push({ name: 'clients' })
  } finally {
    loading.value = false
  }
}

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
    if (isEdit.value) {
      await post(`/api/client/update/${route.params.id}`, client.value)
      success('Client updated')
    } else {
      await post('/api/client/create', client.value)
      success('Client created')
    }
    router.push({ name: 'clients' })
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
    <h1 class="text-xl font-bold text-gray-900 mb-6">{{ title }}</h1>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit" class="bg-white rounded-lg shadow p-6 max-w-2xl">
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

        <div class="grid grid-cols-3 gap-4">
          <BaseInput
            v-model="client.zip"
            label="ZIP"
          />
          <div class="col-span-2">
            <BaseInput
              v-model="client.city"
              label="City"
            />
          </div>
        </div>
      </div>

      <FormActions
        :loading="saving"
        :back-route="{ name: 'clients' }"
      />
    </form>
  </div>
</template>
