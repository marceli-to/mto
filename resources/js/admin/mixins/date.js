export default {
  methods: {

    // get todays date
    // @return str formatted date (i.e. 20.11.2019)
    dateToday() {
      let date = new Date();
      return date.getDay() + '.' + date.getMonth() + '.' + date.getFullYear()
    },
  }
};
