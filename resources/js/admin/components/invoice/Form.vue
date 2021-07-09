<template>
<div>
  <notifications classes="notification"/>
  <quick-create-client v-bind:is="componentName"></quick-create-client>
  <div v-if="showPositionForm">
    <position-form :record="position"></position-form>
  </div>
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
                <a href="" class="icon-add" @click.prevent="toggleForm('QuickCreateClient')"></a>
              </label>
              <div class="select-wrapper is-wide">
                <select v-model="invoice.client_id" name="client_id">
                  <option :value="null">Please choose...</option>
                  <option v-for="record in clients" :key="record.id" :value="record.id">{{ record.name }} &bull; {{ record.city }}</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <label>Text</label>
              <textarea v-model="invoice.text"></textarea>
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
              <label>Positions <a href="" class="icon-add" @click.prevent="addPosition()"></a></label>
              <div class="grid-invoice-position is-header">
                <div>
                  <div>Periode</div>
                  <div>Description</div>
                  <div>Hours</div>
                  <div>Rate</div>
                  <div class="align-right">Amount</div>
                </div>
              </div>
              <div class="grid-invoice-position is-body" v-for="(position, index) in invoice.positions" :key="index">
                <div v-if="position.is_flat">
                  <div>{{position.periode}}</div>
                  <div>{{position.description}}</div>
                  <div>–</div>
                  <div>Flat</div>
                  <div class="align-right">{{position.amount | formatCurrency}}</div>
                </div>
                <div v-else>
                  <div>{{position.periode}}</div>
                  <div>{{position.description}}</div>
                  <div>{{ position.hours | formatDecimal }}</div>
                  <div>{{position.rate | formatCurrency }}</div>
                  <div class="align-right">{{position.amount | formatCurrency}}</div>
                </div>
                <span class="position-action">
                  <a href="javascript:;"
                    class="icon-edit icon-mini"
                    @click.prevent="editPosition(position)"
                  ></a>
                  <a
                    href="javascript:;"
                    class="icon-trash icon-mini"
                    @click.prevent="deletePosition(position,$event)"
                  ></a>
                </span>
              </div>
              <div class="grid-invoice-total">
                <div>Total</div>
                <div class="align-right">{{ total | formatCurrency }}</div>
                <div>VAT</div>
                <div class="align-right">{{ vat | formatCurrency }}</div>
                <div>Grandtotal</div>
                <div class="align-right">{{ grandtotal | formatCurrency }}</div>
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
import PositionForm from "@/components/invoice/position/Form.vue";

// Helpers
import Utils from "@/mixins/utils";
import Quick from "@/mixins/quick";
import Date from "@/mixins/date";
import Progress from "@/mixins/progress";
import { TheMask } from "vue-the-mask";

export default {
  components: {
    FormButtons: FormButtons,
    QuickCreateClient: QuickCreateClient,
    PositionForm: PositionForm,
    TheMask: TheMask
  },

  props: {
    type: String
  },

  mixins: [Utils, Quick, Date, Progress],

  data() {
    return {
      errors: {
        title: false,
      },

      invoice: {
        title: null,
        text: null,
        date: '',
        date_due: '',
        positions: [],
        total: null,
        vat: null,
        grandtotal: null,
      },

      clients: null,
      componentName: '',
      hasForm: false,
      position: null,
      showPositionForm: false,

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
    },

    addPosition() {
      this.position = null;
      this.togglePositionForm();
    },

    editPosition(position) {
      this.position = position;
      this.togglePositionForm();
    },

    deletePosition(position,event) {
      if (confirm("Bitte löschen bestätigen!")) {
        const index = this.invoice.positions.findIndex(x => x.id === position.id);
        this.invoice.positions.splice(index,1);

        if (position.id != null) {
          let uri = `/api/invoice/position/destroy/${position.id}`;
          let el = this.progress(event.target);
          this.axios.delete(uri).then(response => {
            this.$notify({ type: "success", text: "Record deleted" });
            this.progress(el);
          });
        }
      }
    },

    togglePositionForm() {
      this.showPositionForm = this.showPositionForm ? false : true;
      this.hasForm = this.hasForm ? false : true;
    },
  },

  computed: {
    title() {
      return this.$props.type == "edit"
        ? "Edit invoice"
        : "Add invoice";
    },

    total() {
      this.invoice.total = _.sumBy(this.invoice.positions, function(o) { return parseFloat(o.amount); })
      return this.invoice.total;
    },

    vat() {
      this.invoice.vat = Math.ceil((this.invoice.total / 100 * 7.7) * 20) / 20;
      return this.invoice.vat;
    },

    grandtotal() {
      this.invoice.grandtotal = this.invoice.total + this.invoice.vat;
      return this.invoice.grandtotal;
    }
  }
};
</script>