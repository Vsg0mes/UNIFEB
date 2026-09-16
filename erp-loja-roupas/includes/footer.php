<?php
/**
 * includes/footer.php
 * Rodapé HTML compartilhado por todas as páginas do sistema.
 * Fecha as tags abertas no header.php e carrega os scripts JS.
 */
?>
    <!-- ===================== FOOTER ===================== -->
    <footer class="footer-main mt-auto py-3">
        <div class="container-fluid text-center">
            <small class="text-muted">
                <i class="bi bi-shop me-1"></i>
                ERP Moda &copy; <?= date('Y') ?> &mdash; Todos os direitos reservados
            </small>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS (inclui Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN"
            crossorigin="anonymous"></script>

    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <!-- JS customizado -->
    <script src="<?= BASE_URL ?>public/js/scripts.js"></script>

    <!-- Bloco para scripts específicos de página -->
    <?php if (!empty($pageScripts)) echo $pageScripts; ?>

</body>
</html>
