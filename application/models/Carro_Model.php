<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Carro_Model extends CI_Model {

    const API_KEY = "";
    const API_KEY_ADM = "";
    const DIR_UPLOAD = 'carros';
    const FULL_DIR_UPLOAD = 'uploads/' . self::DIR_UPLOAD . '/';

    public function __construct() {
        parent::__construct();

        $this->load->helper('date');
    }

    public function selectAll(array $params = null): array {
        $query = $this->db->select()->from('carros');
        $query->where('tipo != ', "empresa");
        if (isset($params['tipo'])) {
            $query->where('tipo', $params['tipo']);
        }
        if (isset($params['search'])) {
            $query->like('nome', $params['search']);
        }
        $query->order_by('id', 'DESC');
        if (isset($params['ini']) && isset($params['qtd']) && (int) $params['qtd'] > 0) {
            $query->limit((int) $params['qtd'], (int) $params['ini']);
        }
        return $this->db->get()->result_array();
    }

    public function selectById($id) {
        $this->db->select()->from('carros')->where('id', (int) $id);
        $result = $this->db->get()->result_object();
        return $result ? $result[0] : null;
    }

    public function insert($data): int {
        $data['date_post'] = date_getDateTimeISO();
        if (!$this->db->insert('carros', $data)) {
            throw new Exception($this->db->error_message());
        }

        return $this->db->insert_id();
    }

    public function update($data): bool {
        $data['date_update'] = date_getDateTimeISO();
        $this->db->where('id', (int) $data['id']);
        if (!$this->db->update('carros', $data)) {
            throw new Exception($this->db->error_message());
        }

        return true;
    }

    public function delete($id): bool {
        if ((int) $id <= 0) {
            throw new Exception("Dados não informados.");
        }
        if (!$this->db->delete('carros', ['id' => (int) $id])) {
            throw new Exception($this->db->error_message());
        }
        return true;
    }

}
