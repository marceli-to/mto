<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { PhArrowLeft, PhFilePdf } from '@phosphor-icons/vue'
import QuoteForm from './QuoteForm.vue'

const route = useRoute()
const router = useRouter()

const quoteId = computed(() => route.params.id || null)
const number = ref(null)

const pageTitle = computed(() => {
  if (!quoteId.value) return 'New Quote'
  return number.value ? `Quote ${number.value}` : 'Edit Quote'
})

function onLoaded(data) {
  number.value = data.number
}

// A new quote continues on its edit page; saving an existing one stays put
function onSaved(id) {
  if (!quoteId.value) {
    router.replace({ name: 'quote-edit', params: { id } })
  }
}

function close() {
  router.push({ name: 'quotes' })
}

function openPdf() {
  window.open(`/quote/pdf/${quoteId.value}`, '_blank')
}
</script>

<template>
  <div class="max-w-4xl">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">
      <div class="flex items-center gap-2">
        <router-link
          :to="{ name: 'quotes' }"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-sm transition-colors"
          title="Back to quotes"
        >
          <PhArrowLeft class="w-4 h-4" />
        </router-link>
        <h1 class="text-xl text-gray-900 font-bold">
          {{ pageTitle }}
        </h1>
      </div>

      <button
        v-if="quoteId"
        type="button"
        @click="openPdf"
        class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
        title="Open PDF (saved state)"
      >
        <PhFilePdf class="w-5 h-5" />
      </button>
    </div>

    <QuoteForm
      :quote-id="quoteId"
      @loaded="onLoaded"
      @saved="onSaved"
      @cancel="close"
    />
  </div>
</template>
