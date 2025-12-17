<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  options: {
    type: Array,
    default: () => []
  },
  optionLabel: {
    type: String,
    default: 'label'
  },
  optionValue: {
    type: String,
    default: 'value'
  },
  placeholder: String,
  error: String,
  required: Boolean,
  disabled: Boolean
})

const emit = defineEmits(['update:modelValue'])

const selectClasses = computed(() => [
  'w-full px-3 py-3 border rounded-md transition-all appearance-none bg-white text-sm',
  'focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300',
  props.error
    ? 'border-red-300 bg-red-50'
    : 'border-gray-200',
  props.disabled && 'bg-gray-50 cursor-not-allowed'
])
</script>

<template>
  <div class="space-y-1">
    <label v-if="label" class="block text-sm text-gray-500 mb-2">
      {{ error ?? label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative">
      <select
        :value="modelValue"
        :disabled="disabled"
        :class="selectClasses"
        @change="emit('update:modelValue', $event.target.value)"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option
          v-for="option in options"
          :key="option[optionValue] ?? option"
          :value="option[optionValue] ?? option"
        >
          {{ option[optionLabel] ?? option }}
        </option>
      </select>
      <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
        <svg class="w-5 h-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </div>
    </div>
  </div>
</template>
