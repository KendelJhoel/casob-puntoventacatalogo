<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Contratos\Facturable;
use App\Modelos\ProductoFisico;

/**
 * Servicio que gestiona el carrito de compras.
 *
 * Acepta cualquier ítem que implemente Facturable, permitiendo
 * totalizar ítems heterogéneos mediante el método común calcularPrecioFinal().
 * Al emitir el ticket, lo persiste en disco como JSON.
 */
class Carrito
{
    /** @var Facturable[] */
    private array $items = [];

    private string $directorioTickets;

    public function __construct(string $directorioTickets = __DIR__ . '/../../tickets')
    {
        $this->directorioTickets = $directorioTickets;

        // Asegurar que el directorio de tickets exista.
        if (!is_dir($this->directorioTickets)) {
            mkdir($this->directorioTickets, 0755, true);
        }
    }

    /**
     * Agrega un ítem al carrito. Si es un ProductoFisico, reduce su stock
     * en la cantidad indicada (encapsulamiento: la validación ocurre dentro del modelo).
     *
     * @throws \App\Excepciones\StockInsuficienteException si el stock es insuficiente.
     */
    public function agregarItem(Facturable $item, int $cantidad = 1): void
    {
        // El carrito delega la validación de stock al propio modelo.
        if ($item instanceof ProductoFisico) {
            $item->reducirStock($cantidad);
        }

        // Cada unidad se agrega como entrada individual para el ticket.
        for ($i = 0; $i < $cantidad; $i++) {
            $this->items[] = $item;
        }
    }

    /**
     * Totaliza todos los ítems del carrito mediante el método polimórfico
     * calcularPrecioFinal(), que cada ítem implementa de forma diferente.
     */
    public function calcularTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->calcularPrecioFinal();
        }
        return $total;
    }

    /**
     * Genera el ticket en pantalla y lo persiste como archivo JSON en disco.
     *
     * @return string La ruta del archivo JSON generado.
     */
    public function emitirTicket(): string
    {
        $timestamp = date('Ymd_His');
        $nombreArchivo = "ticket_{$timestamp}.json";
        $rutaCompleta = $this->directorioTickets . '/' . $nombreArchivo;

        $detallesItems = [];
        foreach ($this->items as $item) {
            $detallesItems[] = [
                'detalle' => $item->obtenerDetalle(),
                'precio_final' => round($item->calcularPrecioFinal(), 2),
            ];
        }

        $datosTicket = [
            'numero_ticket' => $timestamp,
            'fecha_emision' => date('Y-m-d H:i:s'),
            'items' => $detallesItems,
            'total' => round($this->calcularTotal(), 2),
        ];

        // Persistencia en archivo JSON (manejo de archivos).
        $jsonContenido = json_encode($datosTicket, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($rutaCompleta, $jsonContenido);

        return $rutaCompleta;
    }

    /** @return Facturable[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
