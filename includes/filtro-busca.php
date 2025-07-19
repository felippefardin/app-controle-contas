<form method="GET" class="search-bar">
  <div class="input-group">
    <i class="fa fa-user"></i>
    <input type="text" name="fornecedor" placeholder="Fornecedor" value="<?= htmlspecialchars($_GET['fornecedor'] ?? '') ?>">
  </div>
  <div class="input-group">
    <i class="fa fa-file"></i>
    <input type="text" name="numero" placeholder="Número" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
  </div>
  <div class="input-group">
    <i class="fa fa-money-bill"></i>
    <input type="number" step="0.01" name="valor" placeholder="Valor" value="<?= htmlspecialchars($_GET['valor'] ?? '') ?>">
  </div>
  <div class="input-group">
    <i class="fa fa-calendar"></i>
    <input type="date" name="data_vencimento" value="<?= htmlspecialchars($_GET['data_vencimento'] ?? '') ?>">
  </div>

  <button type="submit" class="btn-acao">Buscar</button>
  <a href="<?= basename($_SERVER['PHP_SELF']) ?>"><button type="button" class="btn-acao">Limpar Filtros</button></a>
</form>
