{{--
  Base styles for the PDFs that were laid out against Tailwind's CDN build
  (invoice, expense). Fagus runs no scripts, so instead of the CDN this is
  the part of Tailwind's reset those templates rely on, plus the few
  utility classes they use. Add a utility here before using it in a PDF.
--}}
<style>
  *, ::before, ::after {
    box-sizing: border-box;
    border-width: 0;
    border-style: solid;
    border-color: #e5e7eb;
  }
  html {
    line-height: 1.5;
  }
  body {
    margin: 0;
    line-height: inherit;
  }
  h1, h2, h3, h4, h5, h6 {
    font-size: inherit;
    font-weight: inherit;
  }
  h1, h2, h3, h4, h5, h6, p, blockquote, figure, hr, pre {
    margin: 0;
  }
  table {
    text-indent: 0;
    border-color: inherit;
    border-collapse: collapse;
  }
  img, svg {
    display: block;
    vertical-align: middle;
  }
  img {
    max-width: 100%;
    height: auto;
  }

  .flex { display: flex; }
  .grid { display: grid; }
  .grid-cols-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
  .col-span-5 { grid-column: span 5 / span 5; }
  .col-span-7 { grid-column: span 7 / span 7; }
  .justify-between { justify-content: space-between; }
  .w-full { width: 100%; }
  .font-light { font-weight: 300; }
  .font-medium { font-weight: 500; }
</style>
