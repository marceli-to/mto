<template>
  <div>
    <div class="quick-form">
      <div>
        <div>
          <a href="" @click.prevent="toggleForm()" class="icon-close-quick"></a>
          <h1>Add a new client</h1>
          <form @submit.prevent="submit">
            <div class="">
              <div class="form-row" :class="errors.name ? 'has-error': ''">
                <label>Name *</label>
                <input
                  type="text"
                  @focus="removeError('name')"
                  name="name"
                  v-model="client.name">
              </div>
              <div class="form-row">
                <label>City</label>
                <input 
                  type="text" 
                  name="name" 
                  v-model="client.city">
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

  mixins: [Helpers, Quick],

  data() {
    return {
      errors: {
        name: false,
      },

      client: {
        name: null,
        city: null,
      },
    };
  },

  methods: {

    validate() {
      if (!this.client.name) {
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
      this.axios.post(`/api/client/create`, this.client).then(response => {
        this.$parent.fetchClients();
        this.$parent.toggleForm();
      });
    },

    toggleForm() {
      this.$parent.toggleForm();
    }
  },
};
</script>