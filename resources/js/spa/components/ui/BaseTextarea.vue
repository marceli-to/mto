<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: String,
  label: String,
  placeholder: String,
  error: String,
  required: Boolean,
  disabled: Boolean,
  rows: {
    type: [String, Number],
    default: 5
  }
})

const emit = defineEmits(['update:modelValue'])

// Attributes (@focus, @blur, spellcheck, …) belong on the textarea, not the wrapper.
defineOptions({ inheritAttrs: false })

const textareaClasses = computed(() => [
  'w-full px-3 py-3 border rounded-md transition-all text-sm leading-relaxed',
  'focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300',
  props.error
    ? 'border-red-300 bg-red-50'
    : 'border-gray-200 bg-white',
  props.disabled && 'bg-gray-50 cursor-not-allowed'
])
</script>

<template>
  <div class="space-y-2">
    <label v-if="label" class="block text-sm text-gray-500 mb-2">
      {{ error ?? label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <textarea
      v-bind="$attrs"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :rows="rows"
      :class="textareaClasses"
      @input="emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>
