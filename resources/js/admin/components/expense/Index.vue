<template>
  <div>
    <page-header/>
    <notifications classes="notification"/>
    <div class="container">
      <main class="content" role="main">
        <div>
          <h1>Expenses</h1>
          <router-link :to="{ name: 'expense-create' }" class="btn-add">
            <span>Add</span>
          </router-link>
          <!-- <div class="select-wrapper">
            <select @change="filter($event.target.value)">
              <option value="2021">2021</option>
              <option value="2020">2020</option>
            </select>
          </div> -->
          <div class="list-items" v-if="expenses.length">
            <div class="list-item" v-for="expense in expenses" :key="expense.id" data-icons="3">
              <div class="list-item-body">
                <div>
                  {{ expense.dateFormated }} &bull; {{ expense.title }} &bull; {{ expense.amount | formatCurrency }} &bull; {{ expense.number }}
                </div>
              </div>
              <div class="list-item-action" data-icons="3">
                <router-link
                  :to="{name: 'expense-download', params: { id: expense.id }}"
                  class="icon-document icon-mini"
                  target="_blank"
                ></router-link>
                <router-link
                  :to="{name: 'expense-edit', params: { id: expense.id }}"
                  class="icon-edit icon-mini"
                ></router-link>
                <a
                  href="javascript:;"
                  class="icon-trash icon-mini"
                  @click.prevent="destroy(expense.id,$event)"
                ></a>
              </div>
            </div>
            <div class="grid-invoices-total">
              <div>Total</div>
              <div class="align-right">{{ total | formatCurrency }}</div>
            </div>
          </div>
          <div v-else>
            <p>No records available...</p>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
<script>
// Views
import PageHeader from "@/layout/PageHeader.vue";

// Helpers
import Progress from "@/mixins/progress";

export default {
  components: {
    PageHeader,
  },

  mixins: [Progress],

  data() {
    return {
      expenses: [],
      total: null,
      expense: null,
    };
  },

  created() {
    this.fetch();
  },

  methods: {
    fetch() {
      let uri = "/api/expenses/get";
      this.axios.get(uri).then(response => {
        this.expenses = response.data.data;
        this.total = response.data.total;
      });
    },

    destroy(id, event) {
      if (confirm("Bitte löschen bestätigen!")) {
        let uri = `/api/expense/destroy/${id}`;
        let el = this.progress(event.target);
        this.axios.delete(uri).then(response => {
          this.fetch();
          this.$notify({ type: "success", text: "Record deleted" });
          this.progress(el);
        });
      }
    },

    filter(year) {
      console.log(year);
    }
  },

};
</script>