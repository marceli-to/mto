<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCopy, PhArchive, PhArrowCounterClockwise, PhClock } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import Flyout from '@/components/ui/Flyout.vue'
import ProjectForm from './ProjectForm.vue'
import ProjectTimeEntries from './ProjectTimeEntries.vue'

const { get, post, del } = useApi()
const { success, error } = useToast()

const projects = ref([])
const search = ref('')
const loading = ref(true)
const activeFilters = ref(['active'])
const deleteDialog = ref({ show: false, id: null, loading: false })
const flyout = ref({ show: false, projectId: null })
const timeFlyout = ref({ show: false, projectId: null, title: '' })

const flyoutTitle = computed(() => flyout.value.projectId ? 'Edit Project' : 'New Project')

function openCreate() {
  flyout.value = { show: true, projectId: null }
}

function openEdit(id) {
  flyout.value = { show: true, projectId: id }
}

function openTimeEntries(project) {
  timeFlyout.value = { show: true, projectId: project.id, title: project.name }
}

function closeTimeFlyout() {
  timeFlyout.value = { show: false, projectId: null, title: '' }
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

const stateFilters = ['active', 'archived']

const stateColors = {
  active: 'bg-blue-100 text-blue-800',
  archived: 'bg-gray-100 text-gray-800'
}

const projectState = (project) => project.is_archive ? 'archived' : 'active'

function toggleFilter(state) {
  const index = activeFilters.value.indexOf(state)
  if (index === -1) {
    activeFilters.value.push(state)
  } else {
    activeFilters.value.splice(index, 1)
  }
}

const filteredProjects = computed(() => {
  let result = projects.value

  if (activeFilters.value.length > 0) {
    result = result.filter(p => activeFilters.value.includes(projectState(p)))
  }

  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(p =>
      p.name?.toLowerCase().includes(q) ||
      p.client?.name?.toLowerCase().includes(q)
    )
  }

  return result
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

async function toggleArchive(project) {
  try {
    const saved = await post(`/api/project/archive/${project.id}`)
    const index = projects.value.findIndex(p => p.id === project.id)
    if (index !== -1) {
      projects.value[index] = { ...projects.value[index], ...saved }
    }
    success(saved.is_archive ? 'Project archived' : 'Project restored')
  } catch (e) {
    error('Failed to change the project state')
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

      <div class="flex items-center gap-1">
        <button
          @click="openCreate"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="Add Project"
        >
          <PhPlus class="w-4 h-4" />
        </button>
        <h1 class="text-xl text-gray-900 font-bold">
          Projects
        </h1>
      </div>

      <!-- Search -->
      <div>
        <SearchInput v-model="search" />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <template v-else>
      <!-- State Filters -->
      <div class="flex items-center gap-2 mb-6">
        <button
          v-for="state in stateFilters"
          :key="state"
          @click="toggleFilter(state)"
          :class="[
            activeFilters.includes(state) ? stateColors[state] : 'bg-gray-100 text-gray-400',
            'px-2 py-1 rounded-md text-xs font-medium capitalize cursor-pointer transition-colors'
          ]"
        >
          {{ state }}
        </button>
      </div>

      <!-- Empty State -->
      <div v-if="filteredProjects.length === 0" class="text-center py-16">
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
            <div class="flex items-center gap-x-6">
              <span :class="[stateColors[projectState(project)], 'px-2 py-1 rounded-md text-xs font-medium capitalize']">
                {{ projectState(project) }}
              </span>
              <div class="flex items-center gap-x-8">
                {{ project.name }}
                <span v-if="project.client" class="font-bold">{{ project.client.acronym }}</span>
              </div>
            </div>
            <div class="flex items-center gap-1">
              <button
                @click="openTimeEntries(project)"
                class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                title="Time entries"
              >
                <PhClock class="w-5 h-5" />
              </button>
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
                @click="toggleArchive(project)"
                class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                :title="project.is_archive ? 'Restore' : 'Archive'"
              >
                <component :is="project.is_archive ? PhArrowCounterClockwise : PhArchive" class="w-5 h-5" />
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
    </template>

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

    <Flyout
      :show="timeFlyout.show"
      :title="timeFlyout.title"
      size="xl"
      @close="closeTimeFlyout"
    >
      <ProjectTimeEntries :project-id="timeFlyout.projectId" />
    </Flyout>
  </div>
</template>
