<?php
if (!defined('SUPPLY_CHAIN')) {
    http_response_code(403);
    exit('Acesso negado.');
}
?>
</main>
<footer class="rodape">
    <small>Supply Chain &middot; <?= date('Y') ?></small>
</footer>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>