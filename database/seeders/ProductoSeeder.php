<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marca;
use App\Models\Linea;
use App\Models\Categoria;
use App\Models\UnidadMedida;
use App\Models\Producto;
use App\Models\Proveedor;

class ProductoSeeder extends Seeder
{
    public function run()
    {
        $marcas = Marca::all();
        $lineas = Linea::all();
        $proveedores = Proveedor::all();
        $unidadMedidas = UnidadMedida::all();

        // Productos únicos por catálogo
        $productosPorCategoria = [
            'Fierro y Lámina S.A. de C.V.' => [
                'Láminas y Aceros' => [
                    [
                        'sku' => 'Lámina galvanizada',
                        'nombre' => 'Lámina galvanizada',
                        'unidad' => 'm2',
                        'descripcion' => 'Lámina de acero recubierta con capa de zinc para evitar corrosión, ideal para cubiertas y fachadas industriales. Superficie durable y ligeramente reflectante, común en calibres G60/G90.',
                        'url_img' => 'https://acerosmurillo.com/wp-content/uploads/2021/12/lamina-lozacero.jpg'

                    ],
                    [
                        'sku' => 'Ángulo de acero',
                        'nombre' => 'Ángulo de acero',
                        'unidad' => 'm',
                        'descripcion' => 'Perfil en L de acero estructural, usado para refuerzos, bastidores y estructuras metálicas.',
                        'url_img' => 'https://acerosmurillo.com/wp-content/uploads/2021/12/lamina-lozacero.jpg' // imagen genérica para acero estructural
                    ],
                    [
                        'sku' => 'Lámina negra',
                        'nombre' => 'Lámina negra',
                        'unidad' => 'm2',
                        'descripcion' => 'Lámina de acero sin recubrimiento, resistente y versátil para uso estructural y construcción en general.',
                        'url_img' => 'https://upload.wikimedia.org/wikipedia/commons/2/20/Steel_plate.jpg' // imagen genérica de lámina negra
                    ],
                    [
                        'sku' => 'PTR 2x2',
                        'nombre' => 'PTR 2x2',
                        'unidad' => 'm',
                        'descripcion' => 'Perfil Tubular Rectangular de acero, común en estructuras metálicas por su resistencia y facilidad de soldadura.',
                        'url_img' => 'https://upload.wikimedia.org/wikipedia/commons/1/1f/Steel_rectangular_tube.jpg' // imagen genérica de PTR
                    ],
                    [
                        'sku' => 'Canal U',
                        'nombre' => 'Canal U',
                        'unidad' => 'm',
                        'descripcion' => 'Perfil con forma de U usado para refuerzos y guías en construcción y manufactura.',
                        'url_img' => 'https://upload.wikimedia.org/wikipedia/commons/3/31/Steel_channel.jpg' // imagen genérica de canal U
                    ],
                ],
                'Material de Construcción' => [
                    [
                        'sku' => 'Cemento gris',
                        'nombre' => 'Cemento gris',
                        'unidad' => 'kg',
                        'descripcion' => 'Material aglomerante usado como base en la construcción para formar concreto y morteros.',
                        'url_img' => 'https://media.istockphoto.com/photos/cement-bag-picture-id1200000001'
                    ],
                    [
                        'sku' => 'Block hueco',
                        'nombre' => 'Block hueco',
                        'unidad' => 'pza',
                        'descripcion' => 'Elemento prefabricado de concreto, utilizado para levantar muros y estructuras ligeras.',
                        'url_img' => 'https://media.istockphoto.com/photos/concrete-blocks-picture-id1210000002'
                    ],
                    [
                        'sku' => 'Arena fina',
                        'nombre' => 'Arena fina',
                        'unidad' => 'm3',
                        'descripcion' => 'Agregado fino utilizado en mezclas de mortero, concreto y acabados de obra.',
                        'url_img' => 'https://media.istockphoto.com/photos/fine-sand-picture-id1220000003'
                    ],
                    [
                        'sku' => 'Grava ¾',
                        'nombre' => 'Grava ¾',
                        'unidad' => 'm3',
                        'descripcion' => 'Piedra triturada de tamaño medio, ideal para mezclas de concreto y cimentaciones.',
                        'url_img' => 'https://media.istockphoto.com/photos/crushed-stone-picture-id1230000004'
                    ],
                    [
                        'sku' => 'Varilla 3/8',
                        'nombre' => 'Varilla 3/8',
                        'unidad' => 'm',
                        'descripcion' => 'Barra de acero de alta resistencia utilizada para reforzar estructuras de concreto.',
                        'url_img' => 'https://media.istockphoto.com/photos/rebar-steel-bars-picture-id1240000005'
                    ],
                ],
            ],
            'Truper S.A. de C.V.' => [


                'Herramientas Básicas' => [
                    [
                        'sku' => 'Martillo carpintero',
                        'nombre' => 'Martillo carpintero',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta manual con cabeza de acero y mango ergonómico, usada para clavar o retirar clavos.',
                        'url_img' => 'https://media.istockphoto.com/photos/hammer-picture-id1250000006'
                    ],
                    [
                        'sku' => 'Pinza universal',
                        'nombre' => 'Pinza universal',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta versátil para sujetar, doblar o cortar cables y materiales pequeños.',
                        'url_img' => 'https://media.istockphoto.com/photos/pliers-picture-id1260000007'
                    ],
                    [
                        'sku' => 'Cinta métrica 5m',
                        'nombre' => 'Cinta métrica 5m',
                        'unidad' => 'pza',
                        'descripcion' => 'Instrumento de medición retráctil, comúnmente usado para tomar medidas en obras.',
                        'url_img' => 'https://media.istockphoto.com/photos/tape-measure-picture-id1270000008'
                    ],
                    [
                        'sku' => 'Desarmador plano',
                        'nombre' => 'Desarmador plano',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta manual con punta plana diseñada para apretar o aflojar tornillos ranurados.',
                        'url_img' => 'https://media.istockphoto.com/photos/flat-screwdriver-picture-id1280000009'
                    ],
                    [
                        'sku' => 'Llave inglesa',
                        'nombre' => 'Llave inglesa',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta ajustable utilizada para aflojar o apretar tuercas y pernos de diferentes tamaños.',
                        'url_img' => 'https://media.istockphoto.com/photos/adjustable-wrench-picture-id1290000010'
                    ],
                ],
                'Herramientas Manuales' => [
                    [
                        'sku' => 'Sierra manual',
                        'nombre' => 'Sierra manual',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta de corte con hoja dentada, ideal para trabajos en madera y plástico.',
                        'url_img' => 'https://media.istockphoto.com/photos/hand-saw-picture-id1300000011'
                    ],
                    [
                        'sku' => 'Llave allen',
                        'nombre' => 'Llave allen',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta en forma de "L" utilizada para apretar tornillos con cabeza hexagonal interior.',
                        'url_img' => 'https://media.istockphoto.com/photos/allen-keys-picture-id1310000012'
                    ],
                    [
                        'sku' => 'Cuchilla retráctil',
                        'nombre' => 'Cuchilla retráctil',
                        'unidad' => 'pza',
                        'descripcion' => 'Cúter con hoja deslizable, perfecto para cortes precisos en cartón, plástico y otros materiales ligeros.',
                        'url_img' => 'https://media.istockphoto.com/photos/utility-knife-picture-id1320000013'
                    ],
                    [
                        'sku' => 'Tenaza Truper',
                        'nombre' => 'Tenaza Truper',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta resistente para cortar y manipular alambres, típica en trabajos de construcción y electricidad.',
                        'url_img' => 'https://media.istockphoto.com/photos/pincers-picture-id1330000014'
                    ],
                    [
                        'sku' => 'Espátula metálica',
                        'nombre' => 'Espátula metálica',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta de hoja plana y flexible usada para aplicar y alisar materiales como yeso o pasta.',
                        'url_img' => 'https://media.istockphoto.com/photos/metal-spatula-picture-id1340000015'
                    ],
                ],
                'Herramientas Eléctricas' => [
                    [
                        'sku' => 'Taladro percutor',
                        'nombre' => 'Taladro percutor',
                        'unidad' => 'pza',
                        'descripcion' => 'Taladro tipo martillo que combina rotación y percusión, ideal para perforar concreto y mampostería. Equivalente a versiones compactas inalámbricas como Milwaukee M18.',
                        'url_img' => 'https://media.istockphoto.com/photos/worker-with-hammer-drill-picture-id1270000000'
                    ],
                    [
                        'sku' => 'Rotomartillo',
                        'nombre' => 'Rotomartillo',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta de gran potencia utilizada principalmente para perforar superficies duras como concreto y piedra.',
                        'url_img' => 'https://media.istockphoto.com/photos/construction-worker-using-rotary-hammer-picture-id1250000001'
                    ],
                    [
                        'sku' => 'Pulidora angular',
                        'nombre' => 'Pulidora angular',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta eléctrica diseñada para cortar, desbastar y pulir diferentes materiales como metal, piedra y cerámica.',
                        'url_img' => 'https://media.istockphoto.com/photos/angle-grinder-on-metal-picture-id1300000002'
                    ],
                    [
                        'sku' => 'Caladora eléctrica',
                        'nombre' => 'Caladora eléctrica',
                        'unidad' => 'pza',
                        'descripcion' => 'Sierra de vaivén ideal para realizar cortes curvos y rectos en madera, metal y otros materiales.',
                        'url_img' => 'https://media.istockphoto.com/photos/jigsaw-tool-picture-id1290000003'
                    ],
                    [
                        'sku' => 'Sierra circular',
                        'nombre' => 'Sierra circular',
                        'unidad' => 'pza',
                        'descripcion' => 'Herramienta eléctrica portátil con disco circular para cortes rápidos y rectos en madera, plástico y metal.',
                        'url_img' => 'https://media.istockphoto.com/photos/circular-saw-picture-id1280000004'
                    ]

                ],
                'Accesorios Industriales' => [
                    [
                        'sku' => 'Brocas de acero rápido',
                        'nombre' => 'Brocas de acero rápido',
                        'unidad' => 'cj',
                        'descripcion' => 'Conjunto de brocas fabricadas en acero rápido (HSS), ideales para perforar metal, madera y plástico con alta durabilidad y precisión.',
                        'url_img' => 'https://example.com/images/brocas-acero-rapido.jpg' // imagen genérica herramienta
                    ],
                    [
                        'sku' => 'Disco de corte',
                        'nombre' => 'Disco de corte',
                        'unidad' => 'pza',
                        'descripcion' => 'Disco abrasivo para corte de metales, concreto y materiales duros, compatible con amoladoras angulares.',
                        'url_img' => 'https://example.com/images/disco-corte.jpg' // imagen genérica disco de corte
                    ],
                    [
                        'sku' => 'Cepillo de alambre',
                        'nombre' => 'Cepillo de alambre',
                        'unidad' => 'pza',
                        'descripcion' => 'Cepillo de alambre de acero para limpieza y preparación de superficies metálicas antes de pintura o soldadura.',
                        'url_img' => 'https://example.com/images/cepillo-alambre.jpg' // imagen genérica cepillo
                    ],
                    [
                        'sku' => 'Guantes de carnaza',
                        'nombre' => 'Guantes de carnaza',
                        'unidad' => 'par',
                        'descripcion' => 'Guantes resistentes de carnaza para protección industrial en trabajos de soldadura, manipulación de metales y materiales abrasivos.',
                        'url_img' => 'https://example.com/images/guantes-carnaza.jpg' // imagen genérica guantes
                    ],
                    [
                        'sku' => 'Gafas de seguridad',
                        'nombre' => 'Gafas de seguridad',
                        'unidad' => 'pza',
                        'descripcion' => 'Gafas protectoras resistentes a impactos y polvo, con lentes antiempañantes para uso en talleres y obra.',
                        'url_img' => 'https://example.com/images/gafas-seguridad.jpg' // imagen genérica gafas
                    ],
                ],
            ],
            'Granjas ElGranGero S.A. de C.V.' => [

                'Equipamiento Agroindustrial' => [
                    [
                        'sku' => 'Tolva de alimentación',
                        'nombre' => 'Tolva de alimentación',
                        'unidad' => 'pza',
                        'descripcion' => 'Tolva metálica para almacenamiento y distribución de alimento en procesos agroindustriales, con diseño robusto y capacidad variable.',
                        'url_img' => 'https://example.com/images/tolva-alimentacion.jpg'
                    ],
                    [
                        'sku' => 'Tanque de almacenamiento',
                        'nombre' => 'Tanque de almacenamiento',
                        'unidad' => 'lt',
                        'descripcion' => 'Tanque plástico o metálico para almacenamiento de líquidos, diseñado para resistencia a químicos y condiciones agrícolas.',
                        'url_img' => 'https://example.com/images/tanque-almacenamiento.jpg'
                    ],
                    [
                        'sku' => 'Extractor de aire',
                        'nombre' => 'Extractor de aire',
                        'unidad' => 'pza',
                        'descripcion' => 'Equipo para ventilación y extracción de aire en instalaciones agroindustriales, optimizando condiciones ambientales.',
                        'url_img' => 'https://example.com/images/extractor-aire.jpg'
                    ],
                    [
                        'sku' => 'Molinillo de granos',
                        'nombre' => 'Molinillo de granos',
                        'unidad' => 'pza',
                        'descripcion' => 'Molino compacto para triturar granos y semillas, ideal para producción de alimento balanceado.',
                        'url_img' => 'https://example.com/images/molinillo-granos.jpg'
                    ],
                    [
                        'sku' => 'Sistema de riego',
                        'nombre' => 'Sistema de riego',
                        'unidad' => 'cj',
                        'descripcion' => 'Kit completo de riego por goteo o aspersión para cultivos, con componentes resistentes y fáciles de instalar.',
                        'url_img' => 'https://example.com/images/sistema-riego.jpg'
                    ],
                ],

                'Insumos para Granjas' => [
                    [
                        'sku' => 'Alimento para pollos',
                        'nombre' => 'Alimento para pollos',
                        'unidad' => 'kg',
                        'descripcion' => 'Alimento balanceado para aves de corral, formulado para promover crecimiento y salud óptima.',
                        'url_img' => 'https://example.com/images/alimento-pollos.jpg'
                    ],
                    [
                        'sku' => 'Vacuna multidosis',
                        'nombre' => 'Vacuna multidosis',
                        'unidad' => 'ml',
                        'descripcion' => 'Vacuna para protección de aves contra múltiples enfermedades comunes en granjas avícolas.',
                        'url_img' => 'https://example.com/images/vacuna-multidosis.jpg'
                    ],
                    [
                        'sku' => 'Vitaminas solubles',
                        'nombre' => 'Vitaminas solubles',
                        'unidad' => 'ml',
                        'descripcion' => 'Suplemento vitamínico soluble para administración en agua, que mejora la salud y productividad animal.',
                        'url_img' => 'https://example.com/images/vitaminas-solubles.jpg'
                    ],
                    [
                        'sku' => 'Bebedero automático',
                        'nombre' => 'Bebedero automático',
                        'unidad' => 'pza',
                        'descripcion' => 'Sistema automático para suministro de agua potable, diseñado para reducir desperdicios y mantener limpieza.',
                        'url_img' => 'https://example.com/images/bebedero-automatico.jpg'
                    ],
                    [
                        'sku' => 'Desinfectante agrícola',
                        'nombre' => 'Desinfectante agrícola',
                        'unidad' => 'lt',
                        'descripcion' => 'Producto desinfectante para instalaciones y equipo agrícola, que elimina bacterias y hongos eficientemente.',
                        'url_img' => 'https://example.com/images/desinfectante-agricola.jpg'
                    ],
                ],

                'Mantenimiento de Instalaciones' => [
                    [
                        'sku' => 'Pintura anticorrosiva',
                        'nombre' => 'Pintura anticorrosiva',
                        'unidad' => 'lt',
                        'descripcion' => 'Pintura especializada para protección de superficies metálicas contra corrosión y desgaste ambiental.',
                        'url_img' => 'https://example.com/images/pintura-anticorrosiva.jpg'
                    ],
                    [
                        'sku' => 'Malla ciclónica',
                        'nombre' => 'Malla ciclónica',
                        'unidad' => 'm',
                        'descripcion' => 'Malla metálica galvanizada para cercados, resistente a la intemperie y uso rudo.',
                        'url_img' => 'https://example.com/images/malla-ciclonica.jpg'
                    ],
                    [
                        'sku' => 'Foco infrarrojo',
                        'nombre' => 'Foco infrarrojo',
                        'unidad' => 'pza',
                        'descripcion' => 'Foco para calentamiento por infrarrojos, utilizado en granjas para mantener temperatura óptima en aves y animales.',
                        'url_img' => 'https://example.com/images/foco-infrarrojo.jpg'
                    ],
                    [
                        'sku' => 'Motor de repuesto',
                        'nombre' => 'Motor de repuesto',
                        'unidad' => 'pza',
                        'descripcion' => 'Motor eléctrico compacto para reemplazo en equipos de mantenimiento industrial y agrícola.',
                        'url_img' => 'https://example.com/images/motor-repuesto.jpg'
                    ],
                    [
                        'sku' => 'Kit de herramientas básicas',
                        'nombre' => 'Kit de herramientas básicas',
                        'unidad' => 'jgo',
                        'descripcion' => 'Set básico de herramientas manuales para mantenimiento general en instalaciones y equipos.',
                        'url_img' => 'https://example.com/images/kit-herramientas-basicas.jpg'
                    ],
                ],
            ],


        ];

        foreach ($proveedores as $proveedor) {
            $categoriasPorProveedor = $productosPorCategoria[$proveedor->razon_social] ?? null;
            if (!$categoriasPorProveedor) continue;

            foreach ($categoriasPorProveedor as $categoria_nombre => $productosCategoria) {
                if (!$productosCategoria) continue;

                foreach ($productosCategoria as $productoData) {
                    $unidad = $unidadMedidas->where('descripcion', $productoData['unidad'])->first();

                    // Crear el producto si no existe (evitar duplicados)
                    $producto = Producto::firstOrCreate(
                        [
                            'proveedor_id' =>  $proveedor->id,
                            'sku' => $productoData['sku'],
                            'sku' => $productoData['nombre'],
                            'nombre' => $productoData['nombre'],
                            'descripcion' =>  $productoData['descripcion'],
                            'linea_id' => $lineas->random()->id,
                            'marca_id' => $marcas->random()->id,
                            'unidad_medida_id' => $unidad ? $unidad->id : $unidadMedidas->random()->id,
                        ]
                    );
                    // Asignar categorías (múltiples)
                    $categorias = Categoria::whereIn('nombre', ['Láminas y Aceros'])->get();
                    $producto->categorias()->sync($categorias->pluck('id'));
                }
            }
        }
    }
}
