<?php

/**
 * @file
 *
 */
module_load_include('inc', 'islandora', 'includes/utilities');

/**
 * FedoraCommonsUtil class definition
 */
class FedoraCommonsUtil {

  /**
   * Connection to the Fedora Repository to be used for class functions.
   *
   * @var object
   */
  protected $repo_conn;

  /**
   * Create a FedoraCommonsUtil Object.
   *
   */
  public function __construct() {
  }
}
