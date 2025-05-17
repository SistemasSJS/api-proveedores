<?php

namespace App\Docs;

/**
 * @OA\Tag(
 *     name="Proveedores",
 *     description="Endpoints para la gestión de proveedores"
 * )
 */
class ProveedorDocs
{
  /**
   * @OA\Get(
   *     path="/api/proveedores",
   *     tags={"Proveedores"},
   *     summary="Listar todos los proveedores",
   *     security={{"sanctum":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Lista de proveedores"
   *     )
   * )
   */
  public function indexDoc() {}

  /**
   * @OA\Post(
   *     path="/api/proveedores",
   *     tags={"Proveedores"},
   *     summary="Registrar un nuevo proveedor",
   *     security={{"sanctum":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(ref="#/components/schemas/ProveedorRequest")
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Proveedor creado exitosamente"
   *     )
   * )
   */
  public function storeDoc() {}

  /**
   * @OA\Get(
   *     path="/api/proveedores/{id}",
   *     tags={"Proveedores"},
   *     summary="Obtener un proveedor por ID",
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Proveedor encontrado"
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Proveedor no encontrado"
   *     )
   * )
   */
  public function showDoc() {}

  /**
   * @OA\Put(
   *     path="/api/proveedores/{id}",
   *     tags={"Proveedores"},
   *     summary="Actualizar proveedor existente",
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(ref="#/components/schemas/ProveedorRequest")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Proveedor actualizado"
   *     )
   * )
   */
  public function updateDoc() {}

  /**
   * @OA\Delete(
   *     path="/api/proveedores/{id}",
   *     tags={"Proveedores"},
   *     summary="Eliminar un proveedor",
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=204,
   *         description="Proveedor eliminado"
   *     )
   * )
   */
  public function destroyDoc() {}
}
