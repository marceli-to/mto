<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCopy, PhFilePdf } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import Flyout from '@/components/ui/Flyout.vue'
import QuoteForm from './QuoteForm.vue'
import QuoteStatusForm from './QuoteStatusForm.vue'

const { get, del } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const quotes = ref([])
const search = ref('')
const loading = ref(true)
const activeFilters = ref(['draft', 'sent'])
const deleteDialog = ref({ show: false, id: null, loading: false })
const statusDialog = ref({ show: false, quote: null })
const flyout = ref({ show: false, quoteId: null })

const flyoutTitle = computed(() => flyout.value.quoteId ? 'Edit Quote' : 'New Quote')

function openCreate() {
  flyout.value = { show: true, quoteId: null }
}

function openEdit(id) {
  flyout.value = { show: true, quoteId: id }
}

function closeFlyout() {
  flyout.value = { show: false, quoteId: null }
}

function onQuoteSaved() {
  closeFlyout()
  fetchQuotes()
}

const filteredQuotes = computed(() => {
  let result = quotes.value

  if (activeFilters.value.length > 0) {
    result = result.filter(q => activeFilters.value.includes(q.status))
  }

  if (search.value) {
    const s = search.value.toLowerCase()
    result = result.filter(q =>
      q.title?.toLowerCase().includes(s) ||
      q.client?.name?.toLowerCase().includes(s) ||
      q.number?.toLowerCase().includes(s)
    )
  }

  return result
})

const stateFilters = ['draft', 'sent', 'accepted', 'declined']

function toggleFilter(state) {
  const index = activeFilters.value.indexOf(state)
  if (index === -1) {
    activeFilters.value.push(state)
  } else {
    activeFilters.value.splice(index, 1)
  }
}

const statusColors = {
  draft: 'bg-blue-100 text-blue-800',
  sent: 'bg-yellow-100 text-yellow-800',
  accepted: 'bg-green-100 text-green-800',
  declined: 'bg-red-100 text-red-800'
}

function quoteTotal(quote) {
  if (!quote.sections) return 0
  return quote.sections.reduce((sum, section) => {
    return sum + (section.positions || []).reduce((sSum, p) => sSum + parseFloat(p.amount || 0), 0)
  }, 0)
}

async function fetchQuotes() {
  loading.value = true
  try {
    const data = await get('/api/quotes/get')
    quotes.value = data.data || []
  } catch (e) {
    error('Failed to load quotes')
  } finally {
    loading.value = false
  }
}

async function cloneQuote(id) {
  try {
    await get(`/api/quote/duplicate/${id}`)
    await fetchQuotes()
    success('Quote cloned')
  } catch (e) {
    error('Failed to clone quote')
  }
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteQuote() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/quote/destroy/${deleteDialog.value.id}`)
    await fetchQuotes()
    success('Quote deleted')
    deleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete quote')
  } finally {
    deleteDialog.value.loading = false
  }
}

function openStatusDialog(quote) {
  statusDialog.value = { show: true, quote }
}

function onStatusUpdated() {
  statusDialog.value.show = false
  fetchQuotes()
}

function downloadPdf(id) {
  window.open(`/quote/pdf/${id}`, '_blank')
}

onMounted(fetchQuotes)
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">
      <div class="flex items-center gap-2">
        <button
          @click="openCreate"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="Add Quote"
        >
          <PhPlus class="w-4 h-4" />
        </button>
        <h1 class="text-xl text-gray-900 font-bold">
          Quotes
        </h1>
      </div>

      <div>
        <SearchInput v-model="search" />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredQuotes.length === 0" class="text-center py-16">
      <div class="text-gray-400 mb-2">No quotes found</div>
      <p class="text-sm text-gray-400">Create your first quote to get started</p>
    </div>

    <div v-else>
      <!-- Status Filters -->
      <div class="flex items-center gap-2 mb-6">
        <button
          v-for="state in stateFilters"
          :key="state"
          @click="toggleFilter(state)"
          :class="[
            activeFilters.includes(state) ? statusColors[state] : 'bg-gray-100 text-gray-400',
            'px-2 py-1 rounded-md text-xs font-medium capitalize cursor-pointer transition-colors'
          ]"
        >
          {{ state }}
        </button>
      </div>

      <!-- Quotes List -->
      <div class="overflow-hidden border-t border-gray-100">
        <ul class="divide-y divide-gray-100">
          <li
            v-for="q in filteredQuotes"
            :key="q.id"
            class="flex items-center justify-between py-4 hover:bg-gray-50/50 transition-colors"
          >
            <div class="flex items-center gap-x-6 w-full">
              <button
                @click="openStatusDialog(q)"
                :class="[statusColors[q.status] || 'bg-gray-100', 'px-2 py-1 rounded-md text-xs font-medium capitalize cursor-pointer transition-colors']"
              >
                {{ q.status }}
              </button>
              <div class="flex justify-between w-full">
                <div class="flex items-center gap-x-8">
                  {{ q.number }}
                  <span v-if="q.client" class="font-bold">{{ q.client.acronym }}</span>
                  {{ q.title }}
                </div>
                <div class="text-right pr-4">
                  {{ formatCurrency(quoteTotal(q)) }}
                </div>
              </div>
            </div>
            <div class="flex items-center gap-1">
              <button
                @click="downloadPdf(q.id)"
                class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                title="Download PDF"
              >
                <PhFilePdf class="w-5 h-5" />
              </button>
              <button
                @click="openEdit(q.id)"
                class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                title="Edit"
              >
                <PhPencil class="w-5 h-5" />
              </button>
              <button
                @click="cloneQuote(q.id)"
                class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                title="Clone"
              >
                <PhCopy class="w-5 h-5" />
              </button>
              <button
                @click="confirmDelete(q.id)"
                class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer rounded-sm transition-colors"
                title="Delete"
              >
                <PhTrash class="w-5 h-5" />
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <ConfirmDialog
      :show="deleteDialog.show"
      title="Delete Quote"
      message="Are you sure you want to delete this quote?"
      :loading="deleteDialog.loading"
      @confirm="deleteQuote"
      @cancel="deleteDialog.show = false"
    />

    <QuoteStatusForm
      v-if="statusDialog.show"
      :quote="statusDialog.quote"
      @close="statusDialog.show = false"
      @updated="onStatusUpdated"
    />

    <Flyout
      :show="flyout.show"
      :title="flyoutTitle"
      size="2xl"
      @close="closeFlyout"
    >
      <QuoteForm
        :quote-id="flyout.quoteId"
        @saved="onQuoteSaved"
        @cancel="closeFlyout"
      />
    </Flyout>
  </div>
</template>
