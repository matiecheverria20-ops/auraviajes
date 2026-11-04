<?php
declare(strict_types=1);

require_once __DIR__ . '/data.php';

function public_link(?string $link, string $fallback = 'index.php'): string
{
    $link = trim((string) $link);
    if ($link === '' || $link === '#') {
        return base_url($fallback);
    }
    if (preg_match('#^(https?:)?//#i', $link) === 1 || str_starts_with($link, 'mailto:') || str_starts_with($link, 'tel:')) {
        return $link;
    }
    $path = ltrim(str_replace('\\', '/', $link), '/');
    if (preg_match('#^paquetes\.php\?id=(\d+)#', $path, $matches) === 1) {
        $path = 'paquete.php?id=' . $matches[1];
    }
    if (str_starts_with($path, 'Paginas/')) {
        $path = substr($path, strlen('Paginas/'));
    }
    return base_url($path);
}

function public_media_url(?string $path): ?string
{
    $path = trim((string) $path);
    if ($path === '') {
        return null;
    }
    $normalized = ltrim(str_replace('\\', '/', $path), '/');
    if (preg_match('#^(https?:)?//#i', $normalized) === 1) {
        return $normalized;
    }
    if (str_starts_with($normalized, 'uploads/') || str_starts_with($normalized, 'assets/')) {
        return is_file(APP_ROOT . '/' . $normalized) ? base_url($normalized) : null;
    }
    if (str_contains($normalized, '/')) {
        return null;
    }
    return is_file(APP_ROOT . '/assets/img/' . $normalized) ? asset('img/' . $normalized) : null;
}

function public_banner_image(array $banner): string
{
    return public_media_url($banner['Imagen'] ?? null) ?? destination_image(($banner['Titulo'] ?? '') . ' ' . ($banner['Texto'] ?? ''));
}

function public_package_image(array $package): string
{
    return destination_image(($package['DestinoPrincipal'] ?? '') . ' ' . ($package['Nombre'] ?? ''));
}

function public_package_card(array $package, ?array $price = null): string
{
    $price = $price ?? package_current_price((int) $package['ID_Paquete']);
    $url = base_url('paquete.php?id=' . (int) $package['ID_Paquete']);
    $destino = $package['DestinoPrincipal'] ?? '';
    $meta = [(int) $package['DuracionDias'] . ' días'];
    if ($destino !== '') {
        $meta[] = $destino;
    }
    $salida = $package['ProximaSalida'] ?? null;
    $html = '<article class="package-card">';
    $html .= '<a class="package-media" href="' . e($url) . '"><img src="' . e(public_package_image($package)) . '" alt="' . e($package['Nombre']) . '" loading="lazy">';
    if (!empty($price['has_discount']) && $price['discount_label'] !== null) {
        $html .= '<span class="package-flag">' . e($price['discount_label']) . '</span>';
    }
    $html .= '</a>';
    $html .= '<div class="package-body">';
    $html .= '<div class="package-meta"><span>' . implode('</span><span>', array_map('e', $meta)) . '</span></div>';
    $html .= '<h3><a href="' . e($url) . '">' . e($package['Nombre']) . '</a></h3>';
    $html .= '<p>' . e(summary_text($package['Descripcion'] ?? 'Una experiencia pensada para disfrutar cada momento.', 130)) . '</p>';
    $html .= '<div class="package-footer"><div>';
    if (!empty($price['has_discount'])) {
        $html .= '<span class="price-old">' . e(money($price['base'])) . '</span>';
    }
    $html .= '<span class="price">' . e(money($price['final'])) . '<small>por persona</small></span>';
    $html .= '</div><a class="btn btn-outline btn-sm" href="' . e($url) . '">Ver más</a></div>';
    if ($salida !== null && $salida !== '') {
        $html .= '<span class="small muted">Próxima salida: ' . e(fecha((string) $salida)) . '</span>';
    }
    $html .= '</div></article>';
    return $html;
}

function public_destination_card(array $destination): string
{
    $url = base_url('destino.php?id=' . (int) $destination['ID_Destino']);
    $category = $destination['CategoriaNombre'] ?? '';
    $description = summary_text($destination['Descripcion'] ?? 'Un destino para descubrir a tu ritmo.', 95);
    $html = '<article class="destination-card" style="--card-image:url(\'' . e(destination_image($destination['NombreUbicacion'] ?? '')) . '\')">';
    if ($category !== '') {
        $html .= '<span class="dest-tag">' . e($category) . '</span>';
    }
    $html .= '<div><h3>' . e($destination['NombreUbicacion']) . '</h3><p>' . e($description) . '</p><a class="card-link" href="' . e($url) . '">Explorar destino &rarr;</a></div>';
    $html .= '</article>';
    return $html;
}

function public_packages_query(): string
{
    return "SELECT p.*,
                   (SELECT GROUP_CONCAT(DISTINCT d.NombreUbicacion ORDER BY pd.Orden SEPARATOR ' · ')
                      FROM paquete_destino pd
                      INNER JOIN destino d ON d.ID_Destino = pd.FK_ID_Destino
                     WHERE pd.FK_ID_Paquete = p.ID_Paquete) AS Destinos,
                   (SELECT d.NombreUbicacion
                      FROM paquete_destino pd
                      INNER JOIN destino d ON d.ID_Destino = pd.FK_ID_Destino
                     WHERE pd.FK_ID_Paquete = p.ID_Paquete
                     ORDER BY pd.Orden ASC LIMIT 1) AS DestinoPrincipal,
                   (SELECT MIN(s.FechaSalida) FROM salidas s
                     WHERE s.FK_ID_Paquete = p.ID_Paquete
                       AND s.FechaSalida >= CURDATE()
                       AND s.Estado = 'abierta'
                       AND s.CuposDisponibles > 0) AS ProximaSalida
              FROM paquetes p";
}

function public_offer_packages(int $limit = 9): array
{
    return db_all(
        public_packages_query() . " WHERE p.Estado = 'publicado'
           AND EXISTS (SELECT 1 FROM promocion_paquete pp INNER JOIN promociones pr ON pr.ID_Promocion = pp.FK_ID_Promocion WHERE pp.FK_ID_Paquete = p.ID_Paquete AND pr.Estado = 'activa' AND CURDATE() BETWEEN pr.FechaInicio AND pr.FechaFin)
         ORDER BY (ProximaSalida IS NULL), ProximaSalida ASC, p.Nombre ASC
         LIMIT " . max(1, $limit)
    );
}

function public_message_context(int $packageId, int $departureId = 0): array
{
    $package = null;
    $departure = null;
    if ($packageId > 0) {
        $package = db_one("SELECT ID_Paquete, Codigo, Nombre FROM paquetes WHERE ID_Paquete = :id AND Estado = 'publicado'", [':id' => $packageId]);
    }
    if ($package !== null && $departureId > 0) {
        $departure = db_one(
            'SELECT * FROM salidas WHERE ID_Salida = :id AND FK_ID_Paquete = :paquete',
            [':id' => $departureId, ':paquete' => $packageId]
        );
    }

    $subject = 'Consulta desde la web';
    if ($package !== null) {
        $subject = 'Cotización: ' . $package['Nombre'] . ' (' . $package['Codigo'] . ')';
        if ($departure !== null) {
            $subject .= ' - salida ' . fecha($departure['FechaSalida']);
        }
    }

    return ['package' => $package, 'departure' => $departure, 'subject' => $subject];
}

function public_store_message(array $values, int $packageId = 0, int $departureId = 0): int
{
    $context = public_message_context($packageId, $departureId);
    $subject = trim((string) ($values['Asunto'] ?? ''));
    if ($subject === '') {
        $subject = $context['subject'];
    }
    $message = (string) $values['Mensaje'];
    if ($context['package'] !== null) {
        $message .= "\n\n---\nPaquete: " . $context['package']['Nombre'] . ' (' . $context['package']['Codigo'] . ')';
        if ($context['departure'] !== null) {
            $message .= "\nSalida consultada: " . fecha($context['departure']['FechaSalida']) . ' al ' . fecha($context['departure']['FechaRegreso']);
        }
    }

    $id = db_exec(
        'INSERT INTO formularios_contacto (Nombre, Email, Telefono, Asunto, Mensaje, Estado)
         VALUES (:nombre, :email, :telefono, :asunto, :mensaje, \'nuevo\')',
        [
            ':nombre' => $values['Nombre'],
            ':email' => $values['Email'],
            ':telefono' => $values['Telefono'] !== '' ? $values['Telefono'] : null,
            ':asunto' => mb_substr($subject, 0, 150),
            ':mensaje' => $message,
        ]
    );
    audit('formularios_contacto', 'INSERT', (string) $id, 'Consulta web: ' . $context['subject']);
    return $id;
}

function public_destination(int $id): ?array
{
    return db_one(
        'SELECT d.*, di.Descripcion, di.Clima, di.Moneda, di.Recomendaciones,
                (SELECT c.Nombre FROM destino_categoria dc INNER JOIN categorias_destino c ON c.ID_CategoriaDestino = dc.FK_ID_CategoriaDestino WHERE dc.FK_ID_Destino = d.ID_Destino ORDER BY dc.ID_DestinoCategoria LIMIT 1) AS CategoriaNombre,
                (SELECT COUNT(DISTINCT pd.FK_ID_Paquete) FROM paquete_destino pd INNER JOIN paquetes p ON p.ID_Paquete = pd.FK_ID_Paquete WHERE pd.FK_ID_Destino = d.ID_Destino AND p.Estado = \'publicado\') AS PaquetesCount
           FROM destino d
           LEFT JOIN destino_informacion di ON di.FK_ID_Destino = d.ID_Destino
          WHERE d.ID_Destino = :id',
        [':id' => $id]
    );
}
