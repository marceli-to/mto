<template>
<div>
  <notifications classes="notification"/>
  <quick-create-client v-bind:is="componentName"></quick-create-client>
  <div :class="[hasForm ? 'is-hidden' : '', 'container']">
    <main class="content" role="main">
      <div>
        <h1>{{title}}</h1>
        <form @submit.prevent="submit">
          <div class="grid-3fr-1fr">
            <div>
              <div class="form-row" :class="errors.name ? 'has-error': ''">
                <label>Name *</label>
                <input
                  type="text"
                  @focus="removeError('name')"
                  name="name"
                  v-model="project.name">
              </div>
              <div class="form-row">
                <label>Description</label>
                <textarea></textarea>
              </div>
              <div class="form-row">
                <label>
                  Client
                  <a href="" class="icon-add" @click.prevent="toggleForm('QuickCreateClient')"></a>
                </label>
                <div class="select-wrapper is-wide">
                  <select v-model="project.client_id" name="client_id">
                    <option :value="null">Please choose...</option>
                    <option v-for="record in clients" :key="record.id" :value="record.id">{{ record.name }} &bull; {{ record.city }}</option>
                  </select>
                </div>
              </div>
              <div class="form-row">
                <label>Principal</label>
                <div class="select-wrapper is-wide">
                  <select v-model="project.principal_id" name="principal_id">
                    <option :value="null">Please choose...</option>
                    <option v-for="record in principals" :key="record.id" :value="record.id">{{ record.name }} &bull; {{ record.city }}</option>
                  </select>
                </div>
              </div>
            </div>
            <aside class="sidebar-form">
              <div>
                <div class="form-row is-sm">
                  <label>Budget</label>
                  <input name="budget" v-model="project.budget" type="text" placeholder="10000.00">
                </div>
                <div class="form-row is-sm">
                  <label>Hourly rate</label>
                  <div class="select-wrapper">
                    <select v-model="project.rate_id" name="rate_id">
                      <option :value="null">Please choose...</option>
                      <option v-for="record in rates" :key="record.id" :value="record.id">{{ record.description }}</option>
                    </select>
                  </div>
                </div>
                <div class="form-row is-sm">
                  <label>Collection?</label>
                  <div class="form-radio">
                    <input type="checkbox" v-model="project.is_collection" name="is_collection" id="is_collection" value="1">
                    <label for="is_collection" class="form-control">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </label>
                  </div>
                </div>
                <div class="form-row is-sm">
                  <label>Archive?</label>
                  <div class="form-radio">
                    <input type="checkbox" v-model="project.is_archive" name="is_archive" id="is_archive" value="1">
                    <label for="is_archive" class="form-control">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </label>
                  </div>
                </div>
              </div>
            </aside>
          </div>
          <form-buttons :route="'projects'"></form-buttons>
        </form>
      </div>
    </main>
  </div>
</div>
</template>
<script>

// UI
import FormButtons from "@/components/ui/buttons/FormButtons.vue";

// Views
import QuickCreateClient from "@/components/client/QuickCreate.vue";

// Helpers
import Utils from "@/mixins/utils";
import Quick from "@/mixins/quick";

export default {
  components: {
    FormButtons: FormButtons,
    QuickCreateClient: QuickCreateClient,
  },

  props: {
    type: String
  },

  mixins: [Utils, Quick],

  data() {
    return {
      errors: {
        name: false,
      },

      project: {
        name: null,
      },

      clients: null,
      principals: null,
      rates: null,

      componentName: '',
      hasForm: false,
    };
  },

  created() {

    // get clients
    this.fetchClients();

    // get rates
    this.fetchRates();

    if (this.$props.type == "edit") {
      this.fetchProject(this.$route.params.id);
    }
  },

  methods: {

    fetchClients() {
      this.axios.get(`/api/clients/get`).then(response => {
        this.clients = response.data.data;
        this.principals = response.data.data;
      });
    },

    fetchRates() {
      this.axios.get(`/api/rates/get`).then(response => {
        this.rates = response.data.data;
      });
    },

    fetchProject(id) {
      this.axios.get(`/api/project/edit/${id}`).then(response => {
        this.project = response.data;
      });
    },

    validate() {
      if (this.project.name) {
        return true;
      }
      if (!this.project.name) {
        this.errors.name = true;
      }
      return false;
    },

    submit() {
      if (!this.validate()) {
        this.validationError();
        return;
      }

      if (this.$props.type == "edit") {
        this.update();
      }

      if (this.$props.type == "create") {
        this.store();
      }
    },

    store() {
      let uri = "/api/project/create";
      this.axios.post(uri, this.project).then(response => {
        this.$router.push({ name: "projects" });
      });
    },

    update() {
      let uri = `/api/project/update/${this.$route.params.id}`;
      this.axios.post(uri, this.project).then(response => {
        this.$router.push({ name: "projects" });
      });
    },
  },

  computed: {
    title: function() {
      return this.$props.type == "edit"
        ? "Edit project"
        : "Add project";
    }
  }
};
</script>