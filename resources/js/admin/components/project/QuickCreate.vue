<template>
  <div>
    <div class="quick-form">
      <div>
        <div>
          <a href="" @click.prevent="toggleForm()" class="icon-close-quick"></a>
          <h1>Add a new project</h1>
          <form @submit.prevent="submit">
            <div class="">
              <div class="form-row" :class="errors.name ? 'has-error': ''">
                <label>Name *</label>
                <input
                  type="text"
                  @focus="removeError('name')"
                  name="name"
                  ref="name"
                  v-model="project.name">
              </div>
              <div class="form-row">
                <label>Client</label>
                <div class="select-wrapper is-wide">
                  <select v-model="project.client_id" name="client_id">
                    <option :value="null">Please choose...</option>
                    <option v-for="record in clients" :key="record.id" :value="record.id">{{ record.name }} &bull; {{ record.city }}</option>
                  </select>
                </div>
              </div>
            </div>
            <div>
              <button type="submit" class="btn-secondary">Speichern</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import Helpers from "@/mixins/helpers";
import Quick from "@/mixins/quick";

export default {
  components: {},

  props: {
    type: String
  },

  mixins: [Helpers,Quick],

  data() {
    return {
      errors: {
        name: false,
      },

      project: {
        name: null,
        client_id: null,
        rate_id: 1
      },

      clients: null,
      principals: null,
      rates: null,
    };
  },

  created() {
    
    // remove event listener
    window.removeEventListener('keypress', this.command);

    // get clients
    this.axios.get(`/api/clients/get`).then(response => {
      this.clients = response.data.data;
    });
  },

  methods: {

    validate() {
      if (!this.project.name) {
        this.errors.name = true;
        return false;
      }
      return true;
    },

    submit() {
      if (!this.validate()) {
        this.validationError();
        return false;
      }
      this.store();
    },

    store() {
      this.axios.post(`/api/project/create`, this.project).then(response => {
        this.$router.push({ name: "projects" });
        this.$parent.reload();
      });
    },

    toggleForm() {
      this.$parent.toggleForm();
    }
  },
};
</script>