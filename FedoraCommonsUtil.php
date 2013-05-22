<?php

/**
 * FedoraCommonsUtil class definition
 */
class FedoraCommonsUtil {

  /**
   * Connection to the Fedora Repository to be used for class functions.
   *
   * @var object
   */
  private $tuque_conn;

  /**
   * Flag to indicate logging tool to use. Default is drush_log.
   *
   * @var object
   */
  private $log_type;

  /**
   * Create a FedoraCommonsUtil Object.
   *
   */
  public function __construct(IslandoraTuque $tuque_conn, $log_type = 'drush') {
    $this->tuque_conn = $tuque_conn;
    $this->log_type = $log_type;
  }

  /**
   * 
   *  Given a Fedora namespace and range of numbers generate the full pid
   * 
   * @param type $fedora_namespace
   *  The namespace base that will be used, along with the number range, to
   *  generate the pids
   * 
   * @param type $pid_numbers
   *  A string containing single numeric values and ranges of values, separated
   *  by a comma. Ranges are designated by a dash '-'
   * 
   * 
   * @return type $pid_list
   *  An array of entries consisting of namespace and pid number 
   * 
   */
  function create_pid_list($fedora_namespace, $pid_numbers) {

    $pid_list = array();
    $parse_pids = $this->parse_number_range($pid_numbers);

    foreach ($parse_pids as $parse_pid) {
      $full_pid = $fedora_namespace . ":" . $parse_pid;
      array_push($pid_list, $full_pid);
    }

    return $pid_list;
  }

  /**
   * 
   *  Given Datastream ID and list of pids delete the datastream from these 
   *  Fedora objects
   *
   * 
   * @param type $datastream_id
   *  The datastream id of the dsid you wish to delete from the objects
   * 
   * @param type $pid_numbers
   *  An array containing list of pid numbers to remove datastream from
   * 
   * 
   * @return type 
   * 
   */
  function delete_datastream_by_pid_list($datastream_id, $pid_entries) {

    $delete_count = 0;

    foreach ($pid_entries as $pid_entry) {
      $fedora_object = $this->get_islandora_object($pid_entry);

      if ($fedora_object) {
        $result_code = $fedora_object->purgeDatastream($datastream_id);
        $result_text = 'Failure';

        if ($result_code == 1) {
          $result_text = 'Success';
          $delete_count++;
        }
        $this->log_message("Delete {$datastream_id} datastream from {$pid_entry} - Result : {$result_text}", 'ok');
      }
    }
  }

  /**
   * 
   *  Given a Fedora namespace return all pids associated with the namespace
   * 
   * @param type $fedora_namespace
   *  The namespace base to get related pids
   * 
   * @return type $pid_list
   *  An array of entries consisting of pids under this namespace
   * 
   */
  function get_pid_list_by_namespace($fedora_namespace) {

    $repo = $this->tuque_conn->repository;

    // SPARQL query to get all objects in a given namespace.
    $query =
        "
    SELECT ?obj
    FROM <#ri>
    WHERE {
      ?obj <info:fedora/fedora-system:def/model#hasModel> ?model .
      FILTER(regex(str(?obj), '^info:fedora/$fedora_namespace:'))
    }
  ";

    // Execute the query.
    $results = $repo->ri->sparqlQuery($query);

    // Put all the pids in an array.
    $pids = array();
    foreach ($results as $result) {
      array_push($pids, $result['obj']['value']);
    }

    return $pids;
  }

  /**
   * 
   *  Given a string value representing ranges and single numbers, extracts and
   *  converts the input into a single array containing unique values.
   *  Example input: 1,2-5,10-12
   *  Return array: 1,2,3,4,5,10,11,12
   * 
   * 
   * @param type $range_values
   *  A string containing single numeric values and ranges of values, separated
   *  by a comma. Ranges are designated by a dash '-'
   * 
   * 
   * @return type $pid_numbers
   *  An array containing a unique list of values extracted from the input range
   * 
   */
  public function parse_number_range($range_values) {

    $number_chunks = split(",", $range_values);
    $pid_numbers = array();

    foreach ($number_chunks as $number_chunk) {
      $range_value = split("-", $number_chunk);

      if (sizeof($range_value) == 1) {
        if (is_numeric($range_value[0])) {
          array_push($pid_numbers, $range_value[0]);
        }
      }

      if (sizeof($range_value) == 2) {
        if (is_numeric($range_value[0]) && is_numeric($range_value[1])) {
          foreach (range($range_value[0], $range_value[1]) as $val) {
            array_push($pid_numbers, $val);
          }
        }
      }
    }
    $pid_numbers = array_unique($pid_numbers);

    return $pid_numbers;
  }

  /**
   * 
   * Helper method to route islandora module functions to single spot for 
   * updating in the case of refactoring in future.     
   * 
   * 
   * @param type $object_id
   * pid of Fedora object to retireve
   * 
   * @return FedoraObject
   *   If the given object id exists in the repository then this returns a
   *   FedoraObject.
   *   If no object was found it returns FALSE which triggers
   *   drupal_page_not_found().
   *   If the object was inaccessible then NULL is returned, and the
   *   access callback is expected to catch that case, triggering
   *   drupal_access_denied().
   * 
   */
  private function get_islandora_object($object_id) {
    return islandora_object_load($object_id);
  }

  /**
   * 
   *  Helper log method to route internal log messages, follows drush_logging
   *  signature
   * 
   * 
   * @param type $message
   * 
   * @param type $type
   * 
   * @param type $error
   * 
   * 
   */
  private function log_message($message, $type = 'notice', $error = null) {

    if ($this->log_type === 'drush') {
      drush_log($message, $type, $error);
    }

    if ($this->log_type === 'watchdog') {
      watchdog($message, $type);
    }
  }

}
