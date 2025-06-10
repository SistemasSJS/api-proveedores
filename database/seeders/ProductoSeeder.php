<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marca;
use App\Models\Linea;
use App\Models\Categoria;
use App\Models\UnidadMedida;
use App\Models\Catalogo;
use App\Models\Producto;

class ProductoSeeder extends Seeder
{
    public function run()
    {
        $marcas = Marca::all();
        $lineas = Linea::all();
        $categorias = Categoria::all();
        $unidadMedidas = UnidadMedida::all();
        $catalogos = Catalogo::with('proveedor')->get();

        // Productos únicos por catálogo
        $productosPorCatalogo = [
            'Láminas y Aceros' => [
                [
                    'nombre' => 'Lámina galvanizada',
                    'unidad' => 'm2',
                    'descripcion' => 'Lámina de acero recubierta con capa de zinc para evitar corrosión, ideal para cubiertas y fachadas industriales. Superficie durable y ligeramente reflectante, común en calibres G60/G90.',
                    'url_img' => 'https://acerosmurillo.com/product/lamina-galvanizada/'
                ],
                [
                    'nombre' => 'Ángulo de acero',
                    'unidad' => 'm',
                    'descripcion' => 'Perfil en L de acero estructural, usado para refuerzos, bastidores y estructuras metálicas.',
                    'url_img' => 'https://acerosmurillo.com/product/lamina-galvanizada/' // imagen genérica para acero estructural
                ],
                [
                    'nombre' => 'Lámina negra',
                    'unidad' => 'm2',
                    'descripcion' => 'Lámina de acero sin recubrimiento, resistente y versátil para uso estructural y construcción en general.',
                    'url_img' => 'https://upload.wikimedia.org/wikipedia/commons/2/20/Steel_plate.jpg' // imagen genérica de lámina negra
                ],
                [
                    'nombre' => 'PTR 2x2',
                    'unidad' => 'm',
                    'descripcion' => 'Perfil Tubular Rectangular de acero, común en estructuras metálicas por su resistencia y facilidad de soldadura.',
                    'url_img' => 'https://upload.wikimedia.org/wikipedia/commons/1/1f/Steel_rectangular_tube.jpg' // imagen genérica de PTR
                ],
                [
                    'nombre' => 'Canal U',
                    'unidad' => 'm',
                    'descripcion' => 'Perfil con forma de U usado para refuerzos y guías en construcción y manufactura.',
                    'url_img' => 'https://upload.wikimedia.org/wikipedia/commons/3/31/Steel_channel.jpg' // imagen genérica de canal U
                ],
            ],
            'Material de Construcción' => [
                [
                    'nombre' => 'Cemento gris',
                    'unidad' => 'kg',
                    'descripcion' => 'Material aglomerante usado como base en la construcción para formar concreto y morteros.',
                    'url_img' => 'https://media.istockphoto.com/photos/cement-bag-picture-id1200000001'
                ],
                [
                    'nombre' => 'Block hueco',
                    'unidad' => 'pza',
                    'descripcion' => 'Elemento prefabricado de concreto, utilizado para levantar muros y estructuras ligeras.',
                    'url_img' => 'https://media.istockphoto.com/photos/concrete-blocks-picture-id1210000002'
                ],
                [
                    'nombre' => 'Arena fina',
                    'unidad' => 'm3',
                    'descripcion' => 'Agregado fino utilizado en mezclas de mortero, concreto y acabados de obra.',
                    'url_img' => 'https://media.istockphoto.com/photos/fine-sand-picture-id1220000003'
                ],
                [
                    'nombre' => 'Grava ¾',
                    'unidad' => 'm3',
                    'descripcion' => 'Piedra triturada de tamaño medio, ideal para mezclas de concreto y cimentaciones.',
                    'url_img' => 'https://media.istockphoto.com/photos/crushed-stone-picture-id1230000004'
                ],
                [
                    'nombre' => 'Varilla 3/8',
                    'unidad' => 'm',
                    'descripcion' => 'Barra de acero de alta resistencia utilizada para reforzar estructuras de concreto.',
                    'url_img' => 'https://media.istockphoto.com/photos/rebar-steel-bars-picture-id1240000005'
                ],
            ],
            'Herramientas Básicas' => [
                [
                    'nombre' => 'Martillo carpintero',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta manual con cabeza de acero y mango ergonómico, usada para clavar o retirar clavos.',
                    'url_img' => 'https://media.istockphoto.com/photos/hammer-picture-id1250000006'
                ],
                [
                    'nombre' => 'Pinza universal',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta versátil para sujetar, doblar o cortar cables y materiales pequeños.',
                    'url_img' => 'https://media.istockphoto.com/photos/pliers-picture-id1260000007'
                ],
                [
                    'nombre' => 'Cinta métrica 5m',
                    'unidad' => 'pza',
                    'descripcion' => 'Instrumento de medición retráctil, comúnmente usado para tomar medidas en obras.',
                    'url_img' => 'https://media.istockphoto.com/photos/tape-measure-picture-id1270000008'
                ],
                [
                    'nombre' => 'Desarmador plano',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta manual con punta plana diseñada para apretar o aflojar tornillos ranurados.',
                    'url_img' => 'https://media.istockphoto.com/photos/flat-screwdriver-picture-id1280000009'
                ],
                [
                    'nombre' => 'Llave inglesa',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta ajustable utilizada para aflojar o apretar tuercas y pernos de diferentes tamaños.',
                    'url_img' => 'https://media.istockphoto.com/photos/adjustable-wrench-picture-id1290000010'
                ],
            ],
            'Herramientas Manuales' => [
                [
                    'nombre' => 'Sierra manual',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta de corte con hoja dentada, ideal para trabajos en madera y plástico.',
                    'url_img' => 'https://media.istockphoto.com/photos/hand-saw-picture-id1300000011'
                ],
                [
                    'nombre' => 'Llave allen',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta en forma de "L" utilizada para apretar tornillos con cabeza hexagonal interior.',
                    'url_img' => 'https://media.istockphoto.com/photos/allen-keys-picture-id1310000012'
                ],
                [
                    'nombre' => 'Cuchilla retráctil',
                    'unidad' => 'pza',
                    'descripcion' => 'Cúter con hoja deslizable, perfecto para cortes precisos en cartón, plástico y otros materiales ligeros.',
                    'url_img' => 'https://media.istockphoto.com/photos/utility-knife-picture-id1320000013'
                ],
                [
                    'nombre' => 'Tenaza Truper',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta resistente para cortar y manipular alambres, típica en trabajos de construcción y electricidad.',
                    'url_img' => 'https://media.istockphoto.com/photos/pincers-picture-id1330000014'
                ],
                [
                    'nombre' => 'Espátula metálica',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta de hoja plana y flexible usada para aplicar y alisar materiales como yeso o pasta.',
                    'url_img' => 'https://media.istockphoto.com/photos/metal-spatula-picture-id1340000015'
                ],
            ],
            'Herramientas Eléctricas' => [
                [
                    'nombre' => 'Taladro percutor',
                    'unidad' => 'pza',
                    'descripcion' => 'Taladro tipo martillo que combina rotación y percusión, ideal para perforar concreto y mampostería. Equivalente a versiones compactas inalámbricas como Milwaukee M18.',
                    'url_img' => 'https://media.istockphoto.com/photos/worker-with-hammer-drill-picture-id1270000000'
                ],
                [
                    'nombre' => 'Rotomartillo',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta de gran potencia utilizada principalmente para perforar superficies duras como concreto y piedra.',
                    'url_img' => 'https://media.istockphoto.com/photos/construction-worker-using-rotary-hammer-picture-id1250000001'
                ],
                [
                    'nombre' => 'Pulidora angular',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta eléctrica diseñada para cortar, desbastar y pulir diferentes materiales como metal, piedra y cerámica.',
                    'url_img' => 'https://media.istockphoto.com/photos/angle-grinder-on-metal-picture-id1300000002'
                ],
                [
                    'nombre' => 'Caladora eléctrica',
                    'unidad' => 'pza',
                    'descripcion' => 'Sierra de vaivén ideal para realizar cortes curvos y rectos en madera, metal y otros materiales.',
                    'url_img' => 'https://media.istockphoto.com/photos/jigsaw-tool-picture-id1290000003'
                ],
                [
                    'nombre' => 'Sierra circular',
                    'unidad' => 'pza',
                    'descripcion' => 'Herramienta eléctrica portátil con disco circular para cortes rápidos y rectos en madera, plástico y metal.',
                    'url_img' => 'https://media.istockphoto.com/photos/circular-saw-picture-id1280000004'
                ]

            ],
            'Accesorios Industriales' => [
                [
                    'nombre' => 'Brocas de acero rápido',
                    'unidad' => 'cj',
                    'descripcion' => 'Conjunto de brocas fabricadas en acero rápido (HSS), ideales para perforar metal, madera y plástico con alta durabilidad y precisión.',
                    'url_img' => 'https://example.com/images/brocas-acero-rapido.jpg' // imagen genérica herramienta
                ],
                [
                    'nombre' => 'Disco de corte',
                    'unidad' => 'pza',
                    'descripcion' => 'Disco abrasivo para corte de metales, concreto y materiales duros, compatible con amoladoras angulares.',
                    'url_img' => 'https://example.com/images/disco-corte.jpg' // imagen genérica disco de corte
                ],
                [
                    'nombre' => 'Cepillo de alambre',
                    'unidad' => 'pza',
                    'descripcion' => 'Cepillo de alambre de acero para limpieza y preparación de superficies metálicas antes de pintura o soldadura.',
                    'url_img' => 'https://example.com/images/cepillo-alambre.jpg' // imagen genérica cepillo
                ],
                [
                    'nombre' => 'Guantes de carnaza',
                    'unidad' => 'par',
                    'descripcion' => 'Guantes resistentes de carnaza para protección industrial en trabajos de soldadura, manipulación de metales y materiales abrasivos.',
                    'url_img' => 'https://example.com/images/guantes-carnaza.jpg' // imagen genérica guantes
                ],
                [
                    'nombre' => 'Gafas de seguridad',
                    'unidad' => 'pza',
                    'descripcion' => 'Gafas protectoras resistentes a impactos y polvo, con lentes antiempañantes para uso en talleres y obra.',
                    'url_img' => 'https://example.com/images/gafas-seguridad.jpg' // imagen genérica gafas
                ],
            ],

            'Equipamiento Agroindustrial' => [
                [
                    'nombre' => 'Tolva de alimentación',
                    'unidad' => 'pza',
                    'descripcion' => 'Tolva metálica para almacenamiento y distribución de alimento en procesos agroindustriales, con diseño robusto y capacidad variable.',
                    'url_img' => 'https://example.com/images/tolva-alimentacion.jpg'
                ],
                [
                    'nombre' => 'Tanque de almacenamiento',
                    'unidad' => 'lt',
                    'descripcion' => 'Tanque plástico o metálico para almacenamiento de líquidos, diseñado para resistencia a químicos y condiciones agrícolas.',
                    'url_img' => 'https://example.com/images/tanque-almacenamiento.jpg'
                ],
                [
                    'nombre' => 'Extractor de aire',
                    'unidad' => 'pza',
                    'descripcion' => 'Equipo para ventilación y extracción de aire en instalaciones agroindustriales, optimizando condiciones ambientales.',
                    'url_img' => 'https://example.com/images/extractor-aire.jpg'
                ],
                [
                    'nombre' => 'Molinillo de granos',
                    'unidad' => 'pza',
                    'descripcion' => 'Molino compacto para triturar granos y semillas, ideal para producción de alimento balanceado.',
                    'url_img' => 'https://example.com/images/molinillo-granos.jpg'
                ],
                [
                    'nombre' => 'Sistema de riego',
                    'unidad' => 'cj',
                    'descripcion' => 'Kit completo de riego por goteo o aspersión para cultivos, con componentes resistentes y fáciles de instalar.',
                    'url_img' => 'https://example.com/images/sistema-riego.jpg'
                ],
            ],

            'Insumos para Granjas' => [
                [
                    'nombre' => 'Alimento para pollos',
                    'unidad' => 'kg',
                    'descripcion' => 'Alimento balanceado para aves de corral, formulado para promover crecimiento y salud óptima.',
                    'url_img' => 'https://example.com/images/alimento-pollos.jpg'
                ],
                [
                    'nombre' => 'Vacuna multidosis',
                    'unidad' => 'ml',
                    'descripcion' => 'Vacuna para protección de aves contra múltiples enfermedades comunes en granjas avícolas.',
                    'url_img' => 'https://example.com/images/vacuna-multidosis.jpg'
                ],
                [
                    'nombre' => 'Vitaminas solubles',
                    'unidad' => 'ml',
                    'descripcion' => 'Suplemento vitamínico soluble para administración en agua, que mejora la salud y productividad animal.',
                    'url_img' => 'https://example.com/images/vitaminas-solubles.jpg'
                ],
                [
                    'nombre' => 'Bebedero automático',
                    'unidad' => 'pza',
                    'descripcion' => 'Sistema automático para suministro de agua potable, diseñado para reducir desperdicios y mantener limpieza.',
                    'url_img' => 'https://example.com/images/bebedero-automatico.jpg'
                ],
                [
                    'nombre' => 'Desinfectante agrícola',
                    'unidad' => 'lt',
                    'descripcion' => 'Producto desinfectante para instalaciones y equipo agrícola, que elimina bacterias y hongos eficientemente.',
                    'url_img' => 'https://example.com/images/desinfectante-agricola.jpg'
                ],
            ],

            'Mantenimiento de Instalaciones' => [
                [
                    'nombre' => 'Pintura anticorrosiva',
                    'unidad' => 'lt',
                    'descripcion' => 'Pintura especializada para protección de superficies metálicas contra corrosión y desgaste ambiental.',
                    'url_img' => 'https://example.com/images/pintura-anticorrosiva.jpg'
                ],
                [
                    'nombre' => 'Malla ciclónica',
                    'unidad' => 'm',
                    'descripcion' => 'Malla metálica galvanizada para cercados, resistente a la intemperie y uso rudo.',
                    'url_img' => 'https://example.com/images/malla-ciclonica.jpg'
                ],
                [
                    'nombre' => 'Foco infrarrojo',
                    'unidad' => 'pza',
                    'descripcion' => 'Foco para calentamiento por infrarrojos, utilizado en granjas para mantener temperatura óptima en aves y animales.',
                    'url_img' => 'https://example.com/images/foco-infrarrojo.jpg'
                ],
                [
                    'nombre' => 'Motor de repuesto',
                    'unidad' => 'pza',
                    'descripcion' => 'Motor eléctrico compacto para reemplazo en equipos de mantenimiento industrial y agrícola.',
                    'url_img' => 'https://example.com/images/motor-repuesto.jpg'
                ],
                [
                    'nombre' => 'Kit de herramientas básicas',
                    'unidad' => 'jgo',
                    'descripcion' => 'Set básico de herramientas manuales para mantenimiento general en instalaciones y equipos.',
                    'url_img' => 'https://example.com/images/kit-herramientas-basicas.jpg'
                ],
            ],

        ];

        foreach ($catalogos as $catalogo) {
            $productosCatalogo = $productosPorCatalogo[$catalogo->nombre] ?? null;

            if (!$productosCatalogo) continue;

            foreach ($productosCatalogo as $productoData) {
                // Buscar la unidad por descripción
                $unidad = $unidadMedidas->where('descripcion', $productoData['unidad'])->first();

                // Crear el producto si no existe (evitar duplicados)
                $producto = Producto::firstOrCreate(
                    [
                        'catalogo_id' => $catalogo->id,
                        'nombre' => $productoData['nombre'],
                    ],
                    [
                        'unidad_medida_id' => $unidad ? $unidad->id : $unidadMedidas->random()->id,
                        'linea_id' => $lineas->random()->id,
                        'marca_id' => $marcas->random()->id,
                    ]
                );

                // Relacionar con 1 a 3 categorías aleatorias
                $producto->categorias()->sync(
                    $categorias->random(rand(1, 3))->pluck('id')->toArray()
                );
            }
        }
    }
}
