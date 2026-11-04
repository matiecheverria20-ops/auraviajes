<?php
declare(strict_types=1);

$branch = null;
try {
    $branch = db_one('SELECT Nombre, Localidad, Ubicacion FROM establecimiento ORDER BY ID_Establecimiento LIMIT 1');
} catch (Throwable) {
    $branch = null;
}
$branchName = $branch['Nombre'] ?? 'Sucursal Formosa Centro';
$branchAddress = trim(($branch['Ubicacion'] ?? 'Av. 25 de Mayo 850') . ' — ' . ($branch['Localidad'] ?? 'Formosa'));
?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand-light" href="<?= e(base_url('index.php')) ?>">
                <img src="<?= e(asset('img/logo.png')) ?>" alt="Aura Viajes" class="brand-logo">
                <span class="brand-text">Aura<span>Viajes</span></span>
            </a>
            <p>Viajes pensados para disfrutar, con atención personalizada de principio a fin.</p>
            <div class="footer-social" aria-label="Redes sociales">
                <a href="#" aria-label="Instagram">IG</a>
                <a href="#" aria-label="Facebook">FB</a>
                <a href="#" aria-label="WhatsApp">WA</a>
            </div>
        </div>
        <div>
            <h3>Explorar</h3>
            <a href="<?= e(base_url('destinos.php')) ?>">Destinos</a>
            <a href="<?= e(base_url('paquetes.php')) ?>">Paquetes</a>
            <a href="<?= e(base_url('novedades.php')) ?>">Novedades</a>
            <a href="<?= e(base_url('faq.php')) ?>">Preguntas frecuentes</a>
        </div>
        <div>
            <h3>Atención</h3>
            <a href="<?= e(base_url('contacto.php')) ?>">Contacto</a>
            <a href="<?= e(base_url('cotizar.php')) ?>">Solicitar cotización</a>
            <a href="tel:08002222872">0800-222-AURA</a>
            <a href="mailto:info@auraviajes.com">info@auraviajes.com</a>
        </div>
        <div>
            <h3>Sucursal principal</h3>
            <p class="footer-muted"><?= e($branchName) ?></p>
            <p class="footer-muted"><?= e($branchAddress) ?></p>
            <p class="footer-muted">Lunes a viernes de 9 a 20 hs<br>Sábados de 9 a 18 hs</p>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <span>&copy; <?= date('Y') ?> Aura Viajes S.A. Todos los derechos reservados.</span>
            <span>Formosa · Resistencia · Posadas · Salta · Mendoza</span>
        </div>
    </div>
</footer>
<button class="to-top" id="to-top" aria-label="Volver arriba">&uarr;</button>
<script src="<?= e(asset('js/public.js')) ?>"></script>
</body>
</html>
