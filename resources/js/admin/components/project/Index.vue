<template>
  <div>
    <page-header/>
    <notifications classes="notification"/>
    <quick-create v-bind:is="componentName"></quick-create>
    <div :class="[hasForm ? 'is-hidden' : '', 'container']">
      <main class="content" role="main">
        <div>
          <h1>Projects</h1>
          <router-link :to="{ name: 'project-create' }" class="btn-add">
            <span>Add</span>
          </router-link>
          <div class="list-items" v-if="projects.length">
            <div
              class="list-item"
              v-for="project in projectList"
              :key="project.id"
              data-icons="3"
            >
              <div class="list-item-body">
                <div><strong>{{ project.name }}</strong> <span v-if="project.client"> &bull; {{ project.client.name}}</span></div>
              </div>
              <div class="list-item-action" data-icons="3">
                <router-link
                  :to="{name: 'project-edit', params: { id: project.id }}"
                  class="icon-edit icon-mini"
                ></router-link>
                <a
                  href="javascript:;"
                  class="icon-copy icon-mini"
                  @click.prevent="clone(project.id,$event)"
                ></a>
                <a
                  href="javascript:;"
                  class="icon-trash icon-mini"
                  @click.prevent="destroy(project.id,$event)"
                ></a>
              </div>
            </div>
          </div>
          <div v-else>
            <p>No records available...</p>
          </div>
          <footer class="form-footer">
            <div>
              <div class="search-wrapper">
                <a href="javascript:;" 
                   class="icon-delete" v-if="search"
                   @click.prevent="clearSearch()"
                >
                </a>
                <input type="text" class="search" v-model="search" placeholder="Suche nach Name">
              </div>
            </div>
          </footer>
        </div>
      </main>
    </div>
  </div>
</template>
<script>

// Views
import PageHeader from "@/layout/PageHeader.vue";
import QuickCreate from "@/components/project/QuickCreate.vue";

// Helpers
import Progress from "@/mixins/progress";
import Quick from "@/mixins/quick";

export default {
  components: {
    PageHeader: PageHeader,
    QuickCreate: QuickCreate,
  },

  mixins: [Progress, Quick],

  data() {
    return {
      projects: [],
      search: '',
      componentName: '',
      hasForm: false,
    };
  },

  created() {
    this.fetch();
  	window.addEventListener('keypress', this.command);
  },
  
  destroyed() {
    window.removeEventListener('keypress', this.command);
  },

  methods: {

    command(e) {
      if (e.keyCode == 43) {
        this.toggleForm('QuickCreate');
      }
      if (e.keyCode == 45) {
        this.toggleForm(null);
      }
    },

    fetch() {
      let uri = "/api/projects/get";
      this.axios.get(uri).then(response => {
        this.projects = response.data.data;
      });
    },

    reload() {
      this.toggleForm();
      this.fetch();
    },

    destroy(id,event) {
      if (confirm("Bitte löschen bestätigen!")) {
        let uri = `/api/project/destroy/${id}`;
        let el = this.progress(event.target);
        this.axios.delete(uri).then(response => {
          this.fetch();
          this.$notify({ type: "success", text: "Record deleted" });
          this.progress(el);
        });
      }
    },

    clone(id,event) {
      let uri = `/api/project/clone/${id}`;
      let el = this.progress(event.target);
      this.axios.get(uri).then(response => {
        this.$notify({ type: "success", text: "Record cloned" });
        this.progress(el);
        this.fetch();
      });
    },
    
    clearSearch() {
      this.search = '';
    },
  },

  computed: {
    projectList() {
      return this.projects.filter(project => {
        let name = project.name;
        if (
          name.toLowerCase().includes(this.search.toLowerCase())
        ) {
          return project;
        }
      })
    }
  }
};
</script>