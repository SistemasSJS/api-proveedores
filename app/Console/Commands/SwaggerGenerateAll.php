<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Swagger\{
    ControllerDocGenerator,
    ModelSchemaGenerator,
    RequestGenerator,
    ResourceGenerator,
    ResponseSchemaGenerator,
    SwaggerConfigUpdater
};

class SwaggerGenerateAll extends Command
{
    protected $signature = 'swagger:generate-all';
    protected $description = 'Genera documentación Swagger para modelos, requests, resources y controladores';

    public function handle()
    {
        (new ModelSchemaGenerator)->generate();
        (new RequestGenerator)->generate();
        (new ResourceGenerator)->generate();
        (new ControllerDocGenerator)->generate();
        (new ResponseSchemaGenerator)->generate();
        (new SwaggerConfigUpdater)->update();

        $this->info('🚀 Documentación Swagger generada exitosamente.');
    }
}
