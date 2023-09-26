<?php

namespace Drupal\stasco_ckeditor_plugins;

/**
 * Upload file manager interface.
 */
interface UploadFileManagerInterface {

  /**
   * Update file usages for current node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node entity object.
   */
  public function updateFileUsages(Node $node);

  /**
   * Delete file usages for current node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node entity object.
   */
  public function deleteFileUsages(Node $node);

  /**
   * Edit uploaded file for each node, which uses it.
   *
   * @param array $data
   *   File list.
   */
  public function editUploadedFile(array $data);

  /**
   * Replace uploaded file for each node, which uses it.
   *
   * @param int $old_fid
   *   Old file ID.
   * @param int $new_fid
   *   New file ID.
   * @param array $data
   *   List of files.
   */
  public function replaceUploadedFile(int $old_fid, int $new_fid, array $data);

  /**
   * Delete uploaded file from usages.
   *
   * @param int $fid
   *   File ID.
   */
  public function deleteUploadedFile($fid);

  /**
   * Add record in DB related with this file.
   *
   * @param array $data
   *   File info.
   */
  public function addUploadedFileRecord(array $data);

  /**
   * Update record in DB related with this file.
   *
   * @param array $data
   *   File info.
   */
  public function updateUploadedFileRecord(array $data);

  /**
   * Function updateNodeChangeFileDestination.
   *
   * @todo Document this part.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node entity object.
   * @param int $fid
   *   File ID.
   * @param array $data
   *   File properties.
   */
  public function updateNodeChangeFileDestination(Node $node, int $fid, array $data);

  /**
   * Delete record in DB related with this file.
   *
   * @param int $fid
   *   File ID.
   */
  public function deleteUploadedFileRecord(int $fid);

  /**
   * Provide additional file info by file id.
   */
  public function getUploadedFileInfo($fid);

  /**
   * Get all stasco files.
   */
  public function getStascoFiles();

  /**
   * Return true if this file is stasco and using in the book pages.
   */
  public function isUsingStascoFile($fid);

  /**
   * Function getUriByLink.
   *
   * @todo Document this part.
   */
  public function getUriByLink($href);

  /**
   * Function getFileByLink.
   *
   * @todo Document this part.
   */
  public function getFileByLink($href);

  /**
   * Return nodes which use this file.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getNodes($fid);

}
