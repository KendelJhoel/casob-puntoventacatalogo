<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Modelos\ItemFactory;
use App\Modelos\ItemVendible;
use InvalidArgumentException;
use PDO;

/** El catálogo accede a MySQL solo a través de consultas preparadas. [INYECCION-DEPENDENCIAS] [SEGURIDAD] */
final class RepositorioItems
{
    // [INYECCION-DEPENDENCIAS] El repositorio recibe una conexión ya creada.
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return ItemVendible[] */
    public function listar(): array
    {
        // [SEGURIDAD] Los parámetros del usuario se enlazan por separado del SQL.
        $consulta = $this->pdo->prepare('SELECT * FROM items ORDER BY id DESC');
        $consulta->execute();
        $filas = $consulta->fetchAll();
        return array_map(ItemFactory::crear(...), $filas); // [CRUD-READ]
    }

    public function buscar(int $id): ?ItemVendible
    {
        // [SEGURIDAD] Los parámetros del usuario se enlazan por separado del SQL.
        $consulta = $this->pdo->prepare('SELECT * FROM items WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();
        return $fila === false ? null : ItemFactory::crear($fila); // [CRUD-READ]
    }

    public function crear(ItemVendible $item): int
    {
        // [SEGURIDAD] Los parámetros del usuario se enlazan por separado del SQL.
        $consulta = $this->pdo->prepare(
            'INSERT INTO items (sku, tipo, nombre, precio_base, stock, enlace_descarga, fecha_agenda, imagen)
             VALUES (:sku, :tipo, :nombre, :precio_base, :stock, :enlace_descarga, :fecha_agenda, :imagen)'
        );
        $consulta->execute($this->parametros($item)); // [CRUD-CREATE]
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(ItemVendible $item): bool
    {
        if ($item->getDatabaseId() === null) {
            throw new InvalidArgumentException('El ítem todavía no existe en la base.');
        }

        // [SEGURIDAD] Los parámetros del usuario se enlazan por separado del SQL.
        $consulta = $this->pdo->prepare(
            'UPDATE items SET sku = :sku, tipo = :tipo, nombre = :nombre,
             precio_base = :precio_base, stock = :stock, enlace_descarga = :enlace_descarga,
             fecha_agenda = :fecha_agenda, imagen = :imagen WHERE id = :id'
        );
        $parametros = $this->parametros($item);
        $parametros['id'] = $item->getDatabaseId();
        $consulta->execute($parametros); // [CRUD-UPDATE]
        return $consulta->rowCount() > 0;
    }

    public function eliminar(int $id): bool
    {
        // [SEGURIDAD] Los parámetros del usuario se enlazan por separado del SQL.
        $consulta = $this->pdo->prepare('DELETE FROM items WHERE id = :id');
        $consulta->execute(['id' => $id]); // [CRUD-DELETE]
        return $consulta->rowCount() > 0;
    }

    /** @return array<string,int|float|string|null> */
    private function parametros(ItemVendible $item): array
    {
        return [
            'sku' => $item->getId(),
            'tipo' => $item->getTipo(),
            'nombre' => $item->getNombre(),
            'precio_base' => $item->getPrecioBase(),
            'imagen' => $item->getImagen(),
            ...$item->getCamposPropios(),
        ];
    }
}
