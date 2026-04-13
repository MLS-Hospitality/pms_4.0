<?php

class Carparking_model extends CI_Model {
	
	private $table = 'tbl_car_parking';

	public function create($table, $data = array())
	{
		return $this->db->insert($table, $data);
	}

	public function delete($id, $id1, $table)
	{
		$this->db->where($id1 ,$id)
				 ->delete($table);

		return $this->db->affected_rows() ? true : false;
	} 

	public function update($data, $id, $table)
	{
		return $this->db->where($id, $data[$id])
						->update($table, $data);
	}

	public function read($table, $order_by)
	{
		$this->db->select('*');
		$this->db->from($table);
		$this->db->order_by($order_by, 'desc');
		$query = $this->db->get();

		return ($query->num_rows() > 0) ? $query->result() : false;
	} 

	public function readWhere($select, $table, $order_by, $where_in = null)
	{
		$this->db->select('*');
		$this->db->from($table);
		$this->db->order_by($order_by, 'desc');

		if ($where_in != null) {
			foreach ($where_in as $field => $value) {
				$this->db->where_in($field, $value);	
			}
		}

		$query = $this->db->get();
		return ($query->num_rows() > 0) ? $query->result() : false;
	} 

	public function findById($id, $id1, $table)
	{ 
		return $this->db->select("*")
						->from($table)
						->where($id1, $id)
						->get()
						->row();
	} 

	public function countlist()
	{
		$this->db->select('*');
		$this->db->from($this->table);
		$query = $this->db->get();

		return ($query->num_rows() > 0) ? $query->num_rows() : false;
	}
}
