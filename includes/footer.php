<script>
  const defaultFontSize = 16;

  function adjustFontSize(change) {
    const body = document.body;
    const style = window.getComputedStyle(body).getPropertyValue('font-size');
    let fontSize = parseFloat(style);
    fontSize += change;
    if (fontSize < 12) fontSize = 12;
    if (fontSize > 24) fontSize = 24;
    body.style.fontSize = fontSize + 'px';
  }

  function resetFontSize() {
    document.body.style.fontSize = defaultFontSize + 'px';
  }
</script>

</body>
</html>
