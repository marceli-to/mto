Vue.filter('truncate', function (text, length, suffix) {
    if (text.length > length) {
        return text.substring(0, length) + suffix;
    } else {
        return text;
    }
});

Vue.filter('formatDecimal', function(number) {
    return new Intl.NumberFormat('ch-DE', { style: 'decimal', maximumSignificantDigits: 5 }).format(number);
});

// Vue.filter('formatCurrency', function(number) {
//     return new Intl.NumberFormat('ch-DE', { style: 'currency', currency: 'CHF', maximumSignificantDigits: 5 }).format(number);
// });

Vue.filter('formatCurrency', function(number) {
    const formated = parseFloat(number).toFixed(2);
    return String(formated).replace(/(?<!\..*)(\d)(?=(?:\d{3})+(?:\.|$))/g, '$1\'')
});