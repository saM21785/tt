<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Interclub extends CI_Controller {

  /**
   * Index Page for this controller.
   *
   * Maps to the following URL
   *    http://example.com/index.php/welcome
   *  - or -
   *    http://example.com/index.php/welcome/index
   *  - or -
   * Since this controller is set as the default controller in
   * config/routes.php, it's displayed at http://example.com/
   *
   * So any other public methods not prefixed with an underscore will
   * map to /index.php/welcome/<method_name>
   * @see https://codeigniter.com/user_guide/general/urls.html
   */

  function __construct() {
    parent::__construct();
    $this->load->library('layout');
    $this->load->model('Interclub_model','interclub');     
  }

  public function index()
  {
    $this->layout->views('includes/header.inc.php')->views('includes/navbar.inc.php')->views('page_admin.php')->views('includes/footer.inc.php')->view('page_admin_ajax.php');
  }

  public function ajax_list()
  {
      $this->load->helper('url');

      $list = $this->interclub->get_datatables();
      //var_dump($list);
      $data = array();
      $no = $_POST['start'];
      foreach ($list as $interclub) {
          $no++;
          $row = array();
          $row[] = $interclub->id_interclub;
          $row[] = $interclub->date;

          //add html for action
          $row[] = '<a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Hapus" onclick="delete_interclub('.$interclub->id_interclub.')"><i class="glyphicon glyphicon-trash"></i> Delete</a>';
          $data[] = $row;
      }

      $output = array(
                      "draw" => $_POST['draw'],
                      "recordsTotal" => $this->interclub->count_all(),
                      "recordsFiltered" => $this->interclub->count_filtered(),
                      "data" => $data,
              );
      //output to json format
      echo json_encode($output);
  }

  public function ajax_delete($id)
  {
      //Check if interclub contain rencontres
      /*$this->load->model('rencontre_model','rencontre');
      $result = $this->rencontre->get_by_interclub_id($id);
      if(count($result) > 0)//delete images linked to realisation before deleting realisation 
      {
        foreach ($result as $rencontre)
        {
          $this->load->model('rencontre_model','rencontre');
          $this->rencontre->delete_by_id($rencontre->id_rencontre);
        }
      }//delete realisation*/
      $this->interclub->delete_by_id($id);
      echo json_encode(array("status" => TRUE));
  }

  public function ajax_get_interclubs()
  {
      //Select all interclubs
      $interclubs = $this->interclub->get_interclubs();
      echo json_encode(array("status" => TRUE, "interclubs" => $interclubs));
  }

  public function ajax_add()
  {            
      $status = '';
      //ajouter un interclub            
      $data = array(
              'date' => $_POST['date']
          );

      $id = $this->interclub->save($data);
      if($id != null) $status = true;
      echo json_encode(array("status" => $status,'interclub' => $id));
  }

}