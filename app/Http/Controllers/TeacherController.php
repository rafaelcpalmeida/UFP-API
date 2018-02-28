<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SOAPController;
use App\Http\Controllers\MessagesController;
use PHPHtmlParser\Dom;

class TeacherController extends Controller {
    private $dom;
    private $soap;
    private $message;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SOAPController $soap, MessagesController $message, Dom $dom) {
        $this->soap = $soap;
        $this->message = $message;
        $this->dom = $dom;
    }

    public function getTeachers($option) {
        if ($option == "all") {
            $docentes = [];

            foreach (json_decode($this->soap->getDataFromSOAPServer("docentes")->docentesResult)->Docentes as $docente) {
                $docentes[] = array("nome" => $docente->Docente, "sigla" => $docente->Sigla);
            }

            return $this->message->encodeMessage(200, $docentes);
        }

        $docente = [];

        $docenteAux = $this->soap->getDataFromSOAPServer("docente", array("docente" => array("sigla" => $option)));

        $detalhesDocente = str_replace("<hr>", "", json_decode($docenteAux->docenteResult)->Docente);

        $this->dom = new Dom();

        $this->dom->load($detalhesDocente);
        
        return (!empty($this->parseTeacherInformation($this->dom))) ? $this->message->encodeMessage(200, $this->parseTeacherInformation($this->dom)) : $this->message->encodeMessage(404, "No teacher information found");
    }

    private function parseTeacherInformation($info) {
        $teacherInfo = [];
        $atendimentoAux = [];
        $lastUpdated = "";
        $teacherEmail = "";

        $nome = $info->find('b')->text;

        for($i = 0; $i < count($info->find('p')); $i++) {
            try {
            if ($info->find('p')[$i]->text == "Ocupação Semanal do Docente") {
                $aux = 0;
                for($j = ($i+1); $j < count($info->find('p')); $j++) {
                    preg_match("/([\d]{4}-[\d]{2}-[\d]{2})/", $info->find('p')[$j]->text, $ultimaAtualizacao);

                    preg_match("/(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})/", $info->find('p')[$j]->text, $email);

                    if (isset($ultimaAtualizacao) && count($ultimaAtualizacao) > 1)
                        $lastUpdated = $ultimaAtualizacao[0];

                    if (isset($email) && count($email) > 1)
                        $teacherEmail = $email[0];

                    if ($aux < count($info->find('ul'))) {
                        preg_match_all("/(?:<li>)(.*?)(?:<\/li>)/", $info->find('ul')[$aux]->innerHtml, $diasAtendimento);

                        $atendimentoAux[str_replace(":", "", $info->find('p')[$j]->text)] = $diasAtendimento[1];

                        $aux++;
                    }
                }
            }
            } catch(Exception $ex) {}
        }

        $teacherInfo = array("name" => $nome, "schedule" => $atendimentoAux, "last_update" => $lastUpdated, "email" => $teacherEmail);

        return $teacherInfo;
    }
}
