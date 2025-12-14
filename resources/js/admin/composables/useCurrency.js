export function useCurrency() {
  function formatCurrency(number) {
    if (number === null || number === undefined) return '0.00'
    const formatted = parseFloat(number).toFixed(2)
    // Swiss format: 1'234.56
    return String(formatted).replace(/(?<!\..*)(\d)(?=(?:\d{3})+(?:\.|$))/g, "$1'")
  }

  function formatDecimal(number, decimals = 2) {
    if (number === null || number === undefined) return '0'
    return new Intl.NumberFormat('de-CH', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(number)
  }

  function parseCurrency(value) {
    if (!value) return 0
    // Remove Swiss thousand separators and parse
    return parseFloat(String(value).replace(/'/g, '')) || 0
  }

  return {
    formatCurrency,
    formatDecimal,
    parseCurrency
  }
}
