<template>
<div>
  <notifications classes="notification"/>
  <quick-create-client v-bind:is="componentName"></quick-create-client>
  <quick-create-position v-bind:is="componentName"></quick-create-position>
  <div :class="[hasForm ? 'is-hidden' : '', 'container']">
    <main class="content" role="main">
      <div>
        <h1>{{title}}</h1>
        <form @submit.prevent="submit">
          <div>
            <div class="form-row" :class="errors.title ? 'has-error': ''">
              <label>Title *</label>
              <input
                type="text"
                @focus="removeError('title')"
                name="title"
                v-model="invoice.title">
            </div>
            <div class="form-row">
              <label>
                Client
                <a href="" class="icon-add" @click.prevent="toggleForm('QuickCreateClient')">Add</a>
              </label>
              <div class="select-wrapper is-wide">
                <select v-model="invoice.client_id" name="client_id">
                  <option :value="null">Please choose...</option>
                  <option v-for="record in clients" :key="record.id" :value="record.id">{{ record.name }} &bull; {{ record.city }}</option>
                </select>
              </div>
            </div>
            <div class="grid-2x1fr">
              <div class="form-row">
                <label>Date *</label>
                <the-mask
                  type="text"
                  mask="##.##.####"
                  :masked="true"
                  class="is-datetime is-datepicker"
                  name="date"
                  v-model="invoice.date"
                  @blur.native="setDueDate()"
                ></the-mask>
              </div>
              <div class="form-row">
                <label>Date due *</label>
                <the-mask
                  type="text"
                  mask="##.##.####"
                  :masked="true"
                  class="is-datetime is-datepicker"
                  name="date_due"
                  v-model="invoice.date_due"
                ></the-mask>
              </div>
            </div>
            <div class="form-row">
              <label>Positions <a href="" class="icon-add" @click.prevent="toggleForm('QuickCreatePosition')">Add</a></label>
              <div class="grid-table-positions is-header">
                <div>
                  <div><strong>Periode</strong></div>
                  <div><strong>Description</strong></div>
                  <div><strong>Cost type</strong></div>
                  <div class="align-right"><strong>Amount</strong></div>
                </div>
              </div>
              <div class="grid-table-positions is-body" v-for="position in invoice.positions" :key="position.index">
                <div>
                  <div>{{position.periode}}</div>
                  <div>{{position.description}}</div>
                  <div>{{position.rate}}</div>
                  <div class="align-right">{{position.amount}}</div>
                </div>
              </div>
            </div>
          </div>
          <form-buttons :route="'invoices'"></form-buttons>
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
import QuickCreatePosition from "@/components/invoice/QuickPosition.vue";

// Helpers
import Helpers from "@/mixins/helpers";
import Quick from "@/mixins/quick";
import Date from "@/mixins/date";
import { TheMask } from "vue-the-mask";

export default {
  components: {
    FormButtons: FormButtons,
    QuickCreateClient: QuickCreateClient,
    QuickCreatePosition: QuickCreatePosition,
    TheMask: TheMask
  },

  props: {
    type: String
  },

  mixins: [Helpers, Quick, Date],

  data() {
    return {
      errors: {
        title: false,
      },

      invoice: {
        title: null,
        date: '',
        date_due: '',
        positions: [],
      },

      clients: null,
      componentName: '',
      hasForm: false,
    };
  },

  created() {

    // get clients
    this.fetchClients();

    // get invoice data if in edit mode
    if (this.$props.type == "edit") {
      this.fetchInvoice(this.$route.params.id);

    }

    if (this.$props.type == "create") {
      // set date to today's date
      this.invoice.date = moment().format('DD.MM.YYYY');
      this.setDueDate();
    }
  },

  methods: {

    fetchClients() {
      this.axios.get(`/api/clients/get`).then(response => {
        this.clients = response.data.data;
      });
    },

    fetchInvoice(id) {
      this.axios.get(`/api/invoice/edit/${id}`).then(response => {
        this.invoice = response.data;
        this.invoice.date = moment(this.invoice.date).format('DD.MM.YYYY');
        this.invoice.date_due = moment(this.invoice.date_due).format('DD.MM.YYYY');
      });
    },

    validate() {
      if (this.invoice.title) {
        return true;
      }
      if (!this.invoice.title) {
        this.errors.title = true;
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
      let uri = "/api/invoice/create";
      this.axios.post(uri, this.invoice).then(response => {
        this.$router.push({ name: "invoices" });
      });
    },

    update() {
      let uri = `/api/invoice/update/${this.$route.params.id}`;
      this.axios.post(uri, this.invoice).then(response => {
        this.$router.push({ name: "invoices" });
      });
    },

    setDueDate() {
      this.invoice.date_due = moment(this.invoice.date, 'DD.MM.YYYY').add(3, 'w').format('DD.MM.YYYY');
    }
  },

  computed: {
    title: function() {
      return this.$props.type == "edit"
        ? "Edit invoice"
        : "Add invoice";
    }
  }
};
</script>