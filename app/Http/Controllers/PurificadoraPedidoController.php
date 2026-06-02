<?php



namespace App\Http\Controllers;



use App\Http\Requests\PurificadoraPedidoRequest;

use App\Models\PurificadoraPedido;

use App\Traits\ApiResponse;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;



/**

 * Pedidos de agua — Purificadora Colibrí.

 *

 * Flujo de estados: pendiente (0) → en proceso (1) → completado (2) | cancelado (3).

 *

 * Autenticación:

 * - Público: crear pedido (store).

 * - Sanctum: listar, marcar en proceso (enlace), completar y cancelar.

 *

 * Respuestas: formato ApiResponse (status, code, message, data, errors).

 * Fechas en data: ISO 8601 con zona (ej. 2026-06-01T10:00:00-07:00).

 *

 * @see PurificadoraPedidoRequest Validación del body en store (campos en camelCase).

 * @see PurificadoraPedido Filtros de index y constantes ESTADO_*.

 */

class PurificadoraPedidoController extends Controller

{

    use ApiResponse;



    /**

     * Lista todos los pedidos sin paginación, con filtros opcionales.

     *

     * GET /api/purificadora-pedidos

     * Auth: Bearer Sanctum.

     *

     * Query: search, nombre, celular, estado, creacion_desde, creacion_hasta,

     * actualizacion_desde, actualizacion_hasta, estado_fecha_desde, estado_fecha_hasta,

     * sort_by (default created_at), order (default desc).

     */

    public function index(Request $request): JsonResponse

    {

        $filters = $request->only(PurificadoraPedido::getFilters());

        $sortBy = $request->input('sort_by', 'created_at');

        $order = $request->input('order', 'desc');



        $pedidos = PurificadoraPedido::query()

            ->filter($filters)

            ->orderBy($sortBy, $order)

            ->get();



        return $this->success($pedidos, 'Pedidos obtenidos correctamente.');

    }



    /**

     * Registra un pedido desde el formulario público del cliente.

     *

     * POST /api/purificadora-pedidos

     * Auth: ninguna.

     *

     * Body JSON (camelCase): nombre, celular, correo?, calle, numero, colonia,

     * codigoPostal?, municipio? (default Ahome).

     *

     * data.pedido: registro creado (estado 0, pendiente_fecha).

     * data.whatsapp_url: URL GET absoluta del endpoint marcar en proceso (requiere Sanctum al invocarla).

     */

    public function store(PurificadoraPedidoRequest $request): JsonResponse

    {

        $pedido = PurificadoraPedido::create($request->datosPedido());

        $pedido = $pedido->fresh();



        return $this->success([

            'pedido' => $pedido,

            'whatsapp_url' => $pedido->urlWhatsappEnlace(),

        ], 'Pedido registrado correctamente.', 201);

    }



    /**

     * Marca el pedido como en proceso (enlace compartido por WhatsApp).

     *

     * GET /api/purificadora-pedidos/{id}/marcar-pedido-proceso-whatsapp-enlace

     * Auth: Bearer Sanctum.

     *

     * Requiere estado pendiente (0). Actualiza en_proceso_fecha.

     */

    public function marcarPedidoProcesoWhatsappEnlace(int $id): JsonResponse

    {

        $pedido = PurificadoraPedido::query()->find($id);

        if ($pedido === null) {

            return $this->error('Pedido no encontrado.', null, 404);

        }



        if ((int) $pedido->estado !== PurificadoraPedido::ESTADO_PENDIENTE) {

            return $this->error('Solo se puede pasar a en proceso un pedido pendiente.', null, 422);

        }



        $pedido->update([

            'estado' => PurificadoraPedido::ESTADO_EN_PROCESO,

            'en_proceso_fecha' => now(),

        ]);



        return $this->success($pedido->fresh(), 'Pedido marcado en proceso (enlace WhatsApp).');

    }



    /**

     * Marca el pedido como completado.

     *

     * PUT /api/purificadora-pedidos/{id}/completado

     * Auth: Bearer Sanctum.

     *

     * Requiere estado en proceso (1). Actualiza completado_fecha.

     */

    public function marcarCompletado(int $id): JsonResponse

    {

        $pedido = PurificadoraPedido::query()->find($id);

        if ($pedido === null) {

            return $this->error('Pedido no encontrado.', null, 404);

        }



        if ((int) $pedido->estado !== PurificadoraPedido::ESTADO_EN_PROCESO) {

            return $this->error('Solo se puede completar un pedido en proceso.', null, 422);

        }



        $pedido->update([

            'estado' => PurificadoraPedido::ESTADO_COMPLETADO,

            'completado_fecha' => now(),

        ]);



        return $this->success($pedido->fresh(), 'Pedido marcado como completado.');

    }



    /**

     * Cancela el pedido.

     *

     * PUT /api/purificadora-pedidos/{id}/cancelado

     * Auth: Bearer Sanctum.

     *

     * Permitido desde pendiente (0) o en proceso (1). Actualiza cancelado_fecha.

     */

    public function marcarCancelado(int $id): JsonResponse

    {

        $pedido = PurificadoraPedido::query()->find($id);

        if ($pedido === null) {

            return $this->error('Pedido no encontrado.', null, 404);

        }



        if (! in_array((int) $pedido->estado, [

            PurificadoraPedido::ESTADO_PENDIENTE,

            PurificadoraPedido::ESTADO_EN_PROCESO,

        ], true)) {

            return $this->error('No se puede cancelar un pedido completado o ya cancelado.', null, 422);

        }



        $pedido->update([

            'estado' => PurificadoraPedido::ESTADO_CANCELADO,

            'cancelado_fecha' => now(),

        ]);



        return $this->success($pedido->fresh(), 'Pedido cancelado.');

    }

}


