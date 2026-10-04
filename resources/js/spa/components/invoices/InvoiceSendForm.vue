<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPaperPlaneTilt, PhPaperclip, PhArrowLeft, PhPlus } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  invoice: Object
})

const emit = defineEmits(['sent', 'cancel'])

const { get, post } = useApi()
const { success, error } = useToast()

const loading = ref(true)
const sending = ref(false)
const showCc = ref(false)
const contacts = ref([])
const attachment = ref('')
const copyTo = ref('')
const marksPending = ref(false)
const errors = ref({})

const form = ref({
  to: '',
  cc: '',
  subject: '',
  body: ''
})

// Contacts already on the recipient list don't need a shortcut.
const suggestions = computed(() => {
  const used = [...recipientList(form.value.to), ...recipientList(form.value.cc)]
  return contacts.value.filter(c => !used.includes(c.email))
})

function recipientList(value) {
  return String(value || '').split(',').map(e => e.trim()).filter(Boolean)
}

function addRecipient(email) {
  const current = recipientList(form.value.to)
  form.value.to = [...current, email].join(', ')
}

async function fetchDefaults() {
  loading.value = true
  try {
    const data = await get(`/api/invoice/mail/${props.invoice.id}`)
    form.value = {
      to: data.to || '',
      cc: data.cc || '',
      subject: data.subject || '',
      body: data.body || ''
    }
    contacts.value = data.contacts || []
    attachment.value = data.attachment || ''
    copyTo.value = data.copy_to || ''
    marksPending.value = !!data.marks_pending
  } catch (e) {
    error('Failed to prepare the invoice mail')
    emit('cancel')
  } finally {
    loading.value = false
  }
}

async function submit() {
  errors.value = {}
  sending.value = true
  try {
    await post(`/api/invoice/send/${props.invoice.id}`, form.value)
    success(`Invoice sent to ${recipientList(form.value.to).join(', ')}`)
    emit('sent')
  } catch (e) {
    const response = e?.response?.data
    if (response?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(response.errors).map(([key, messages]) => [key, messages[0]])
      )
    }
    error(response?.message || 'Failed to send the invoice')
  } finally {
    sending.value = false
  }
}

onMounted(fetchDefaults)
</script>

<template>
  <div v-if="loading" class="py-16 text-center text-gray-400">
    <div class="animate-pulse">Loading...</div>
  </div>

  <form v-else @submit.prevent="submit" class="space-y-6">
    <!-- What is being sent -->
    <div class="flex items-center justify-between gap-4 pb-6 border-b border-gray-100">
      <div class="flex items-center gap-x-6 text-sm">
        <span class="font-bold">{{ invoice.client?.acronym }}</span>
        <span>{{ invoice.number }}</span>
        <span class="text-gray-500">{{ invoice.title }}</span>
      </div>
      <div class="flex items-center gap-2 text-sm text-gray-400 shrink-0">
        <PhPaperclip class="w-4 h-4" />
        {{ attachment }}
      </div>
    </div>

    <div>
      <BaseInput
        v-model="form.to"
        label="To"
        type="text"
        placeholder="name@example.ch"
        :error="errors.to"
        required
      />
      <div v-if="suggestions.length || !showCc" class="flex flex-wrap items-center gap-2 mt-3">
        <button
          v-for="contact in suggestions"
          :key="contact.email"
          type="button"
          @click="addRecipient(contact.email)"
          class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 text-gray-600 text-xs hover:bg-gray-200 cursor-pointer transition-colors"
          :title="`Add ${contact.email}`"
        >
          <PhPlus class="w-3 h-3" />
          {{ contact.name || contact.email }}
        </button>
        <button
          v-if="!showCc"
          type="button"
          @click="showCc = true"
          class="px-2 py-1 text-xs text-gray-400 hover:text-gray-600 cursor-pointer transition-colors"
        >
          Add CC
        </button>
      </div>
    </div>

    <BaseInput
      v-if="showCc"
      v-model="form.cc"
      label="CC"
      type="text"
      placeholder="name@example.ch"
      :error="errors.cc"
    />

    <BaseInput
      v-model="form.subject"
      label="Subject"
      :error="errors.subject"
      required
    />

    <BaseTextarea
      v-model="form.body"
      label="Message"
      :rows="14"
      :error="errors.body"
      required
    />

    <!-- What happens on send -->
    <ul class="text-xs text-gray-400 space-y-1">
      <li>The invoice PDF is attached automatically.</li>
      <li v-if="copyTo">A copy goes to {{ copyTo }}.</li>
      <li v-if="marksPending">Sending marks this invoice as pending.</li>
    </ul>

    <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200">
      <button
        type="button"
        @click="emit('cancel')"
        class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 cursor-pointer"
      >
        <PhArrowLeft class="w-4 h-4" />
        Cancel
      </button>

      <BaseButton type="submit" :loading="sending">
        <PhPaperPlaneTilt class="w-4 h-4" />
        Send Invoice
      </BaseButton>
    </div>
  </form>
</template>
