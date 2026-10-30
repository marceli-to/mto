<script setup>
import { ref, watch, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue'
import TiptapEditor from '@/components/ui/TiptapEditor.vue'

const props = defineProps({
  block: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['saved', 'cancel'])

const { post } = useApi()
const { success, error } = useToast()

const saving = ref(false)
const errors = ref({})
const form = ref({ title: '', content: '', is_default: false })

function fill() {
  errors.value = {}
  form.value = {
    title: props.block?.title || '',
    content: props.block?.content || '',
    is_default: !!props.block?.is_default
  }
}

watch(() => props.block, fill)
onMounted(fill)

async function submit() {
  errors.value = {}
  if (!form.value.title?.trim()) {
    errors.value.title = 'Title is required'
    return
  }

  saving.value = true
  try {
    if (props.block?.id) {
      await post(`/api/quote-block/update/${props.block.id}`, form.value)
      success('Text block updated')
    } else {
      await post('/api/quote-block/create', form.value)
      success('Text block created')
    }
    emit('saved')
  } catch (e) {
    error('Failed to save text block')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <BaseInput
      v-model="form.title"
      label="Title"
      required
      :error="errors.title"
      @focus="errors.title = null"
    />

    <TiptapEditor v-model="form.content" label="Content" />

    <BaseCheckbox v-model="form.is_default" label="Add to new quotes automatically" />

    <div class="flex items-center justify-end gap-3 mt-8">
      <BaseButton type="button" variant="secondary" @click="emit('cancel')">
        Cancel
      </BaseButton>
      <BaseButton type="submit" :loading="saving">
        {{ block?.id ? 'Update' : 'Create' }}
      </BaseButton>
    </div>
  </form>
</template>
