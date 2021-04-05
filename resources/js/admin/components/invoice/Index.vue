<template>
  <div>
    <page-header/>
    <notifications classes="notification"/>
    <div v-if="hasStateForm">
      <update-state-form :record="invoice"></update-state-form>
    </div>
    <div :class="[hasForm ? 'is-hidden' : '', 'container']">
      <main class="content" role="main">
        <div>
          <h1>Invoices</h1>
          <router-link :to="{ name: 'invoice-create' }" class="btn-add">
            <span>Add</span>
          </router-link>
          <div class="list-items" v-if="invoices.length">
            <div class="list-item" v-for="invoice in invoiceList" :key="invoice.id" data-icons="4">
              <div :class="['list-item-body is-' + invoice.state.description]">
                <div>
                  <a
                    href=""
                    @click.prevent="toggleStateForm(invoice,$event)"
                    :class="['badge-status is-' + invoice.state.description]"
                  >{{ invoice.state.description }}</a>
                  {{ invoice.number }} &bull;
                  <span v-if="invoice.client">
                    <strong>{{ invoice.client.acronym }}</strong>
                  </span>
                  &bull; {{ invoice.title }}
                  <span v-if="invoice.remarks">
                    [{{invoice.remarks}}]
                  </span>
                </div>
              </div>
              <div class="list-item-action" data-icons="4">
                <router-link
                  :to="{name: 'invoice-download', params: { id: invoice.id }}"
                  class="icon-document icon-mini"
                  target="_blank"
                ></router-link>
                <div v-if="invoice.state.description == 'open'">
                  <router-link
                    :to="{name: 'invoice-edit', params: { id: invoice.id }}"
                    class="icon-edit icon-mini"
                  ></router-link>
                </div>
                <div v-else>
                  <span class="icon-edit icon-mini is-disabled"></span>
                </div>
                <a
                  href="javascript:;"
                  class="icon-copy icon-mini"
                  @click.prevent="clone(invoice.id,$event)"
                ></a>
                <div v-if="invoice.state.description == 'open'">
                  <a
                    href="javascript:;"
                    class="icon-trash icon-mini"
                    @click.prevent="destroy(invoice.id,$event)"
                  ></a>
                </div>
                <div v-else>
                  <span class="icon-trash icon-mini is-disabled"></span>
                </div>
              </div>
            </div>
            <div v-if="showSubTotals">
              <div class="grid-invoices-subtotal">
                <div>Total paid</div>
                <div class="align-right">{{ (totals.paid + totals.closed) | formatCurrency }}</div>
              </div>
              <div class="grid-invoices-subtotal">
                <div>Total pending</div>
                <div class="align-right">{{ totals.pending | formatCurrency }}</div>
              </div>
              <div class="grid-invoices-subtotal">
                <div>Total open</div>
                <div class="align-right">{{ totals.open | formatCurrency }}</div>
              </div>
              <div class="grid-invoices-subtotal">
                <div>Total overdue</div>
                <div class="align-right">{{ totals.overdue | formatCurrency }}</div>
              </div>
            </div>
            <div class="grid-invoices-total">
              <div><a href="" @click.prevent="toggleSubTotals()">Total</a></div>
              <div class="align-right">{{ totals.total | formatCurrency }}</div>
            </div>
          </div>
          <div v-else>
            <p>No records available...</p>
          </div>
          <footer class="form-footer">
            <div>
              <div class="search-wrapper">
                <a
                  href="javascript:;"
                  class="icon-delete"
                  v-if="search"
                  @click.prevent="clearSearch()"
                ></a>
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
import UpdateStateForm from "@/components/invoice/state/Form.vue";

// Helpers
import Progress from "@/mixins/progress";

export default {
  components: {
    PageHeader,
    UpdateStateForm,
  },

  mixins: [Progress],

  data() {
    return {
      invoices: [],
      totals: [],
      search: "",

      invoice: null,
      hasStateForm: false,
      showSubTotals: false,
      hasForm: false
    };
  },

  created() {
    this.fetch();
  },

  methods: {
    fetch() {
      let uri = "/api/invoices/get";
      this.axios.get(uri).then(response => {
        this.invoices = response.data.data;
        this.totals = response.data.totals;
      });
    },

    reload() {
      this.fetch();
    },

    destroy(id, event) {
      if (confirm("Bitte löschen bestätigen!")) {
        let uri = `/api/invoice/destroy/${id}`;
        let el = this.progress(event.target);
        this.axios.delete(uri).then(response => {
          this.fetch();
          this.$notify({ type: "success", text: "Record deleted" });
          this.progress(el);
        });
      }
    },

    clone(id, event) {
      let uri = `/api/invoice/clone/${id}`;
      let el = this.progress(event.target);
      this.axios.get(uri).then(response => {
        this.$notify({ type: "success", text: "Record cloned" });
        this.progress(el);
        this.fetch();
      });
    },

    clearSearch() {
      this.search = "";
    },

    toggleStateForm(invoice,event) {
      this.invoice = invoice;
      this.hasStateForm = this.hasStateForm ? false : true;
      this.hasForm = this.hasForm ? false : true;
    },

    toggleSubTotals() {
      this.showSubTotals = this.showSubTotals ? false : true;
    }
  },

  computed: {
    invoiceList() {
      return this.invoices.filter(invoice => {
        let title = invoice.title;
        if (title.toLowerCase().includes(this.search.toLowerCase())) {
          return invoice;
        }
      });
    },

    total() {
      return _.sumBy(this.invoices, function(o) {
        return parseFloat(o.total);
      });
    }
  }
};
</script>