<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Carro_Controller extends REST_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Carro_Model', 'model');
    }

    private function validate($data, $isAdm = false) {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_data((array) $data);
        $this->form_validation->set_rules('tipo', 'Tipo', 'required|trim');
        $this->form_validation->set_rules('nome', 'Nome', 'required|trim');
        $this->form_validation->set_rules('desc', 'Descrição', 'trim');
        $this->form_validation->set_rules('urlFoto', 'URF Foto', 'trim|valid_url');
        $this->form_validation->set_rules('urlInfo', 'URF Info', 'trim|valid_url');
        $this->form_validation->set_rules('urlVideo', 'URF Vídeo', 'trim|valid_url');
        $this->form_validation->set_rules('latitude', 'Latitude', 'trim');
        $this->form_validation->set_rules('longitude', 'Longitude', 'trim');
        if ($isAdm) {
            $this->form_validation->set_rules('api_key', 'API KEY ADM', "required|trim|callback_validate_api_key_adm");
        } else {
            $this->form_validation->set_rules('api_key', 'API KEY', "required|trim|callback_validate_api_key");
        }

        return $this->form_validation->run();
    }

    public function validate_api_key($str): bool {
        if ($str != $this->model::API_KEY && $str != $this->model::API_KEY_ADM) {
            $this->form_validation->set_message('validate_api_key', 'The {field} field is invalid');
            return false;
        }
        return true;
    }

    public function validate_api_key_adm($str): bool {
        if ($str != $this->model::API_KEY_ADM) {
            $this->form_validation->set_message('validate_api_key_adm', 'The {field} field is invalid! You need ADM permissions to complete this request.');
            return false;
        }
        return true;
    }

    public function index_get() {
        $this->load->helper(['pagination', 'url']);
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $page = (int) $this->input->get('page');
		$this->form_validation->set_data(['api_key' => $this->input->get('api_key'), 'page' => $page]);
        $this->form_validation->set_rules('api_key', 'API KEY', 'required|trim|callback_validate_api_key');
        $this->form_validation->set_rules('page', 'page', 'integer|greater_than[0]');

        $response['status'] = "ERROR";

        if (!$this->form_validation->run()) {
            $response["msg"] = validation_errors();
            $this->response($response, REST_Controller::HTTP_OK);

            return ;
        }

        $param = $this->uri->segment(4);
        if ((int) $param <= 0) {
            $qtd = 5;
            $ini = pagination_calculateIni($qtd, $page);
            $paramsDb = ['ini' => $ini, 'qtd' => $qtd];
            switch ($this->uri->segment(3)) {
                case 'tipo':
                    $paramsDb['tipo'] = $param != "todos" ? $param : null;
                    break;

                case 'busca':
                    $paramsDb['search'] = urldecode($param);
                    break;
            }

            $list = $this->model->selectAll($paramsDb);

        } else {
            $list = $this->model->selectById($param);
        }


        $this->set_response($list, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
    }

    public function index_post() {
        $carro = $this->post('carro');
        $carro = is_array($carro) ? $carro : json_decode($carro);
        foreach ($carro ? $carro : [] as $key => $value) {
            $data[$key] = $value;
        }
        $data['api_key'] = $this->post("api_key");

        try {
            if (!$this->validate($data, true)) {
                throw new Exception(validation_errors());
            }
            $response['status'] = "OK";
            $id = (int) $data['id'];
            unset($data['api_key']);
            if ($id > 0) {
                $this->model->update($data);
                $response['msg'] = "Carro alterado com sucesso AWS";
            } else {
                $id = $this->model->insert($data);
                $response['msg'] = "Carro cadastrado com sucesso AWS";
            }

            $response['id'] = $id;

        } catch (Exception $e) {
            $response['status'] = "ERROR";
            $response['msg'] = $e->getMessage();
        }

        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function index_delete() {
        $data['api_key'] = $this->uri->segment(4);
        $data['id'] = (int) $this->uri->segment(3);
        $this->load->helper('file');
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_data((array) $data);
        $this->form_validation->set_rules('id', 'ID', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('api_key', 'API KEY', 'required|trim|callback_validate_api_key_adm');

        $response['status'] = "ERROR";
        if (!$this->form_validation->run()) {
            $response["msg"] = validation_errors();
            $this->response($response, REST_Controller::HTTP_OK);
            return ;
        }
        $selectById = $this->model->selectById($data['id']);
        if (!$selectById) {
            $response["msg"] = "ID inválido";
            $this->response($response, REST_Controller::HTTP_OK);
            return ;
        }

        try {
            $this->model->delete($data['id']);

            if ($selectById->urlFoto) {
                $arrayUrlFoto = explode('/', $selectById->urlFoto);
                file_delFileFromFolder($this->model::FULL_DIR_UPLOAD . $arrayUrlFoto[count($arrayUrlFoto) - 1]);
            }

            $response['status'] = "OK";
            $response["msg"] = "Carro removido com sucesso AWS";

        } catch (Exception $e) {
            $response["msg"] = $e->getMessage();
        }

        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function postFotoBase64_post() {
        $data = ['api_key' => $this->input->post('api_key'),
                 'id' => (int) $this->input->post('id')];
        $this->load->helper(['file', 'image', 'url']);
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('id', 'ID', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('api_key', 'API KEY', 'required|trim|callback_validate_api_key_adm');
        $response["status"] = "ERROR";
        if (!$this->form_validation->run()) {
            $response["msg"] = validation_errors();
            $this->response($response, REST_Controller::HTTP_OK);
            return ;
        }
        $selectById = $this->model->selectById($data['id']);
        if (!$selectById) {
            $response["msg"] = "ID inválido";
            $this->response($response, REST_Controller::HTTP_OK);
            return ;
        }

        unset($data['api_key']);
        $response['status'] = false;

        if (!is_dir($this->model::FULL_DIR_UPLOAD)) {
            mkdir($this->model::FULL_DIR_UPLOAD, 0777, true);
        }

        $valImg = image_getValBase64ImgExt($this->input->post('base64'));
        if (!$valImg) {
            $response['msg'] = 'Imagem inválida';

        } else {
            $fileName = md5(uniqid(time())) . '.' . $valImg[1];
            $pathFile = $this->model::FULL_DIR_UPLOAD . $fileName;

            if (!file_put_contents('./' . $pathFile, $valImg[0])) {
                $response['msg'] = 'Falha no upload';

            } else {
                if ($selectById->urlFoto) {
                    $arrayUrlFoto = explode('/', $selectById->urlFoto);
                    file_delFileFromFolder($this->model::FULL_DIR_UPLOAD . $arrayUrlFoto[count($arrayUrlFoto) - 1]);
                }

                $data['urlFoto'] = base_url() . $pathFile;
                try {
                    $this->model->update($data);

                    $response['status'] = true;
                    $response['msg'] = "Foto enviada com sucesso AWS";
                    $response['url'] = $data['urlFoto'];

                } catch (Exception $e) {
                    $response['msg'] = $e->getMessage();
                }
            }
        }

        if ($response['status']) {
            $response['status'] = "OK";
        } else {
            $response["status"] = "ERROR";
            $response['url'] = "";
        }

        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function teste_get() {
        $this->load->library('form_validation');
        $this->form_validation->set_data($this->get());
        $this->form_validation->set_rules('api_key', 'API KEY', 'required|trim|callback_validate_api_key');
        echo var_dump($this->form_validation->run());
        echo var_dump(validation_errors());
    }

}
