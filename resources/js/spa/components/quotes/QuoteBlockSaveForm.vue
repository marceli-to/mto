<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import Modal from '@/components/ui/Modal.vue'
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue'

// Saves the content of a quote page as a new block in the text block library.
const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  content: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['close', 'saved'])

const { post } = useApi()
const { success, error } = useToast()

const form = ref({ title: '', is_default: false })
const errors = ref({})
const saving = ref(false)

onMounted(() => {
  form.value.title = props.title
})

async function submit() {
  errors.value = {}
  if (!form.value.title?.trim()) {
    errors.value.title = 'Title is required'
    return
  }

  saving.value = true
  try {
    const block = await post('/api/quote-block/create', {
      title: form.value.title.trim(),
      content: props.content,
      is_default: form.value.is_default
    })
    success('Saved to text blocks')
    emit('saved', block)
  } catch (e) {
    error('Failed to save text block')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Modal title="Save to Text Blocks" size="md" @close="emit('close')">
    <form @submit.prevent="submit" class="space-y-6">
      <BaseInput
        v-model="form.title"
        label="Name"
        required
        :error="errors.title"
        @focus="errors.title = null"
      />
      <BaseCheckbox v-model="form.is_default" label="Add to new quotes automatically" />
      <div class="flex justify-end gap-3 pt-2">
        <BaseButton type="button" variant="secondary" @click="emit('close')">Cancel</BaseButton>
        <BaseButton type="submit" :loading="saving">Save</BaseButton>
      </div>
    </form>
  </Modal>
</template>
