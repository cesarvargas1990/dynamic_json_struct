<?php

namespace App\Http\Traits;

use DB;
use Exception;
use stdClass;

trait JsonStructTrait
{
    public function getJsonStruct($params)
    {
        $reglas =  $this->getReglas($params);
        $arr = $this->armarElementosHijoPadre($reglas, $params);
        $item = $this->transformArrayParents($arr);
        $item = $this->array_remove_by_values($item, ['', null, []]);
        return json_encode($item[0]);
    }

    public function getNreglasPadreGrupo()
    {
        $qry = "select * from nreglas where id_tag_padre is null order by orden asc";
        return DB::select($qry);
    }

    public function transformArrayParents($arr)
    {
        $arrVal = [];
        foreach (array_values($arr) as $val) {
            $arrVal[array_keys($val)[0]] = array_values($val)[0];
        }
        return [array_keys($arr)[0] => $arrVal];
    }

    function armarElementosHijoPadre($consulta, $params, $padre = 0)
    {
        try {
            $temp_array = array();
            $item = [];
            foreach ($consulta as $json) {
                $flag_child = false;
                if ($json->id_tag_padre == $padre) {
                    $hijos = $this->armarElementosHijoPadre($consulta, $params, $json->id);
                    if ($hijos) {
                        $flag_child = true;
                        if ($hijos != "") {
                            $item = array(
                                $json->nom_tag => $hijos
                            );
                        }
                    } else {
                        $valor = "";
                        if ($json->tipo_dato == "F") {
                            $valor = $json->query_dato;
                        } else if ($json->tipo_dato == "Q") {
                            $qry = $json->query_dato;
                            $rs = $this->executeQuery($qry, $params);
                            $valor =  $this->getSingleValueQuery($rs);
                        } else if ($json->tipo_dato == "A") {
                            $qry = $json->query_dato;
                            $valor =  $rs = $this->executeQueryArray($qry, $params);
                        } else if ($json->tipo_dato == "B") {
                            $qry = $json->query_dato;
                            $rs = $this->executeQueryArray($qry, $params);
                            $valor =  $this->getQueryValue($rs);
                        } else if ($json->tipo_dato == "C") {
                            $qry = $json->query_dato;
                            $rs = $this->executeQueryArray($qry, $params);
                            $valor =  $this->getQueryValue2($rs);
                        }
                        $item = array(
                            $json->nom_tag => $valor
                        );
                    }
                    if ($flag_child) {
                        $arrVal = [];
                        foreach (array_values($item)[0] as $val) {
                            $arrVal[array_keys($val)[0]] = array_values($val)[0];
                        }
                        $item = [array_keys($item)[0] => $arrVal];
                    }
                    $temp_array[] = $item;
                }
            }
            return $temp_array;
        } catch (Exception $e) {
            echo "Mensaje->  " . $e->getMessage() . ' <br>  <br>' . "Query-> " . json_encode($json) . ' <br>  <br>' . "Parametros->" . json_encode($params);
            die();
        }
    }

    function array_remove_by_values(array $haystack, array $values)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->array_remove_by_values($haystack[$key], $values);
            }

            if (in_array($haystack[$key], $values, true)) {
                unset($haystack[$key]);
            }
        }
        return $haystack;
    }

    public function getReglas($params)
    {
        $qry = "select * from nreglas  
        where ind_habilita = 1 
        order by orden asc, id_tag_padre desc";
        $data = DB::select($qry);
        foreach ($data as $key => $value) {
            if ($value->tipo_dato == "Q" && $value->regla_valor != null) {
                $data[$key]->valor = $this->getSingleValueQuery($this->executeQuery($value->query_dato, $params));
                $evstr = 'if ( "' . $data[$key]->valor . '" ' . $value->regla_valor . ' ) {
                    return "true";
                } else {
                    return "false";
                }';
                $data[$key]->eval = eval($evstr);
                $data[$key]->evstr = $evstr;
                if ($data[$key]->eval == "true") {
                    unset($data[$key]);
                }
            } else {
                $data[$key]->valor = $value->query_dato;
            }
        }
        return $data;
    }

    public function getSingleValueQuery($rs)
    {
        return array_values(get_object_vars($rs))[0];
    }

    public function getQueryValue($rs)
    {
        $arr = [];
        foreach (get_object_vars($rs[0]) as $value) {
            $arr[] = $value;
        }
        return ($arr);
    }

    public function getQueryValue2($rs)
    {
        $arr = [];
        foreach ($rs as $value) {
            $arr[] = array_values(get_object_vars($value))[0];
        }
        return ($arr);
    }

    public function executeQuery($qry, $params)
    {
        return DB::select($qry, $this->solveParams($params, $qry))[0];
    }
    public function executeQueryArray($qry, $params)
    {
        return DB::select($qry, $this->solveParams($params, $qry));
    }
    public function solveParams($params, $qry)
    {
        foreach ($params as $key => $value) {
            if (!strstr($qry, ":" . $key)) {
                unset($params[$key]);
            }
        }
        return $params;
    }

    function pretty_json($json, $ret = "\n", $ind = "\t")
    {

        $beauty_json = '';
        $quote_state = FALSE;
        $level = 0;

        $json_length = strlen($json);

        for ($i = 0; $i < $json_length; $i++) {

            $pre = '';
            $suf = '';

            switch ($json[$i]) {
                case '"':
                    $quote_state = !$quote_state;
                    break;

                case '[':
                    $level++;
                    break;

                case ']':
                    $level--;
                    $pre = $ret;
                    $pre .= str_repeat($ind, $level);
                    break;

                case '{':

                    if ($i - 1 >= 0 && $json[$i - 1] != ',') {
                        $pre = $ret;
                        $pre .= str_repeat($ind, $level);
                    }

                    $level++;
                    $suf = $ret;
                    $suf .= str_repeat($ind, $level);
                    break;

                case ':':
                    $suf = ' ';
                    break;

                case ',':

                    if (!$quote_state) {
                        $suf = $ret;
                        $suf .= str_repeat($ind, $level);
                    }
                    break;

                case '}':
                    $level--;

                case ']':
                    $pre = $ret;
                    $pre .= str_repeat($ind, $level);
                    break;
            }

            $beauty_json .= $pre . $json[$i] . $suf;
        }

        return $beauty_json;
    }

    function markup_json(string $in): string
    {
        $string  = 'green';
        $number  = 'darkorange';
        $null    = 'magenta';
        $key     = 'red';
        $pattern = '/("(\\\\u[a-zA-Z0-9]{4}|\\\\[^u]|[^\\\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/';
        return preg_replace_callback(
            $pattern,
            function (array $matches) use ($string, $number, $null, $key): string {
                $match  = $matches[0];
                $colour = $number;
                if (preg_match('/^"/', $match)) {
                    $colour = preg_match('/:$/', $match)
                        ? $key
                        : $string;
                } elseif ($match === 'null') {
                    $colour = $null;
                }
                return "<span style='color:{$colour}'>{$match}</span>";
            },
            str_replace(['<', '>', '&'], ['&lt;', '&gt;', '&amp;'], $in)
        ) ?? $in;
    }
}
