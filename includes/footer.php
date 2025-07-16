<script>
  const defaultFontSize = 16; // Fonte padrão em pixels

  function adjustFontSize(change) {
    const body = document.body;
    let currentSize = parseFloat(window.getComputedStyle(body).getPropertyValue('font-size'));
    let newSize = currentSize + change;
    if (newSize < 12) newSize = 12;
    if (newSize > 24) newSize = 24;
    body.style.fontSize = newSize + 'px';
  }

  function resetFontSize() {
    document.body.style.fontSize = defaultFontSize + 'px';
  }
</script>

</body>
</html>
