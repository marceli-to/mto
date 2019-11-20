<template>
  <div class="container">
    <notifications classes="notification"/>
    <main class="content" role="main">
      <div>
        <h1>{{title}}</h1>
        <form @submit.prevent="submit">
          <div>
            <div class="form-row" :class="errors.name ? 'has-error': ''">
              <label>Name *</label>
              <input
                type="text"
                @focus="removeError('name')"
                name="name"
                v-model="client.name">
            </div>
            <div class="form-row">
              <label>Byline</label>
              <input
                type="text"
                name="byline"
                v-model="client.byline">
            </div>
            <div class="form-row">
              <label>Street, No.</label>
              <input
                type="text"
                name="street"
                v-model="client.street">
            </div>
            <div class="grid-2x1fr">
              <div class="form-row">
                <label>ZIP</label>
                <input 
                  type="text" 
                  name="name" 
                  v-model="client.zip">
              </div>
              <div class="form-row">
                <label>City</label>
                <input 
                  type="text" 
                  name="name" 
                  v-model="client.city">
              </div>
            </div>
          </div>
          <form-buttons :route="'clients'"></form-buttons>
        </form>
      </div>
    </main>
  </div>
</template>
<script>
import PageHeader from "@/layout/PageHeader.vue";
import FormButtons from "@/components/ui/buttons/FormButtons.vue";
import Helpers from "@/mixins/helpers";

export default {
  components: {
    FormButtons: FormButtons,
  },

  props: {
    type: String
  },

  mixins: [Helpers],

  data() {
    return {
      errors: {
        name: false,
      },

      client: {
        name: null,
        location: null,
        street: null,
        city: null,
        byline: null
      },
    };
  },

  created() {
    if (this.$props.type == "edit") {
      let uri = `/api/client/edit/${this.$route.params.id}`;
      this.axios.get(uri).then(response => {
        this.client = response.data;
      });
    }
  },

  methods: {

    validate() {
      if (this.client.name) {
        return true;
      }

      if (!this.client.name) {
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
      let uri = "/api/client/create";
      this.axios.post(uri, this.client).then(response => {
        this.$router.push({ name: "clients" });
      });
    },

    update() {
      let uri = `/api/client/update/${this.$route.params.id}`;
      this.axios.post(uri, this.client).then(response => {
        this.$router.push({ name: "clients" });
      });
    },
  },

  computed: {
    title: function() {
      return this.$props.type == "edit"
        ? "Edit client"
        : "Add client";
    }
  }
};
</script>