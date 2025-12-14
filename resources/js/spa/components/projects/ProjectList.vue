<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCopy, PhFolder } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import Flyout from '@/components/ui/Flyout.vue'
import ProjectForm from './ProjectForm.vue'

const { get, del } = useApi()
const { success, error } = useToast()

const projects = ref([])
const search = ref('')
const loading = ref(true)
const deleteDialog = ref({ show: false, id: null, loading: false })
const flyout = ref({ show: false, projectId: null })

const flyoutTitle = computed(() => flyout.value.projectId ? 'Edit Project' : 'New Project')

function openCreate() {
  flyout.value = { show: true, projectId: null }
}

function openEdit(id) {
  flyout.value = { show: true, projectId: id }
}

function closeFlyout() {
  flyout.value = { show: false, projectId: null }
}

function onProjectSaved(savedProject) {
  if (flyout.value.projectId) {
    const index = projects.value.findIndex(p => p.id === flyout.value.projectId)
    if (index !== -1) {
      projects.value[index] = { ...projects.value[index], ...savedProject }
    }
  } else {
    projects.value.unshift(savedProject)
  }
  closeFlyout()
}

const filteredProjects = computed(() => {
  if (!search.value) return projects.value
  const q = search.value.toLowerCase()
  return projects.value.filter(p =>
    p.name?.toLowerCase().includes(q) ||
    p.client?.name?.toLowerCase().includes(q)
  )
})

async function fetchProjects() {
  loading.value = true
  try {
    const data = await get('/api/projects/get')
    projects.value = data.data || []
  } catch (e) {
    error('Failed to load projects')
  } finally {
    loading.value = false
  }
}

async function cloneProject(id) {
  try {
    const data = await get(`/api/project/duplicate/${id}`)
    projects.value.unshift(data)
    success('Project cloned')
  } catch (e) {
    error('Failed to clone project')
  }
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteProject() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/project/destroy/${deleteDialog.value.id}`)
    projects.value = projects.value.filter(p => p.id !== deleteDialog.value.id)
    success('Project deleted')
    deleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete project')
  } finally {
    deleteDialog.value.loading = false
  }
}

onMounted(fetchProjects)
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">
      
      <h1 class="text-xl text-gray-900 font-bold">
        Projects
      </h1>

      <!-- Search -->
      <div>
        <SearchInput v-model="search" />
      </div>

      <button
        v-if="!flyout.show"
        @click="openCreate"
        class="fixed right-4 bottom-4 z-20 inline-flex items-center gap-2 pr-4 pl-3 py-2 bg-black text-white text-md rounded-xs hover:bg-gray-800 transition-colors cursor-pointer">
        <PhPlus class="w-5 h-5" />
        Add Project
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredProjects.length === 0" class="text-center py-16">
      <div class="text-gray-400 mb-2">No projects found</div>
      <p class="text-sm text-gray-400">Create your first project to get started</p>
    </div>

    <!-- Projects List -->
    <div v-else class="overflow-hidden border-t border-gray-100">
      <ul class="divide-y divide-gray-100">
        <li
          v-for="project in filteredProjects"
          :key="project.id"
          class="flex items-center justify-between py-4 hover:bg-gray-50/50 transition-colors"
        >
          <div class="flex items-center gap-x-8">
            {{ project.name }}
            <span v-if="project.client" class="font-bold">{{ project.client.acronym }}</span>
          </div>
          <div class="flex items-center gap-1">
            <button
              @click="openEdit(project.id)"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Edit"
            >
              <PhPencil class="w-5 h-5" />
            </button>
            <button
              @click="cloneProject(project.id)"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Clone"
            >
              <PhCopy class="w-5 h-5" />
            </button>
            <button
              @click="confirmDelete(project.id)"
              class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer rounded-sm transition-colors"
              title="Delete"
            >
              <PhTrash class="w-5 h-5" />
            </button>
          </div>
        </li>
      </ul>
    </div>

    <ConfirmDialog
      :show="deleteDialog.show"
      title="Delete Project"
      message="Are you sure you want to delete this project?"
      :loading="deleteDialog.loading"
      @confirm="deleteProject"
      @cancel="deleteDialog.show = false"
    />

    <Flyout
      :show="flyout.show"
      :title="flyoutTitle"
      size="md"
      @close="closeFlyout"
    >
      <ProjectForm
        :project-id="flyout.projectId"
        @saved="onProjectSaved"
        @cancel="closeFlyout"
      />
    </Flyout>
  </div>
</template>
