<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JsonStructTrait;


class JsonStructController extends Controller
{

    use JsonStructTrait;

    public function testJson(Request $request)
    {

        try {

            $nitEmpresa = $request->get('nitEmpresa');
            $docum = $request->get('docum');
            $prefijo = $request->get('prefijo');
            $empleado = $this->getCodigo($docum, $prefijo);
            $params = array(
                'codigo' => $empleado->codigo,
                'nit_empresa' => $nitEmpresa,
                'docum' => $docum
            );
            $flag = $request->get('flag');

            if ($flag == "1") {
                echo  $this->getJsonStruct($params)[0];
                die();
            } else {
                $json = $this->pretty_json($this->markup_json($this->getJsonStruct($params)));
                echo "<pre>" . $json . "</pre>";
            }
        } catch (\Exception $e) {
            return response([
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage()
            ], 500)->header('Content-Type', 'application/json');
        }
    }
}
