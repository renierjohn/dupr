<?php

namespace Drupal\renify_tournament\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

class CsvTemplateController extends ControllerBase {

  public function download() {
    $headers = [
      "DUPR ID Player A", "Name Player A",
      "DUPR ID Player B", "Name Player B",
      "Category", "Bracket", "Remarks"
    ];

    $example_data = [
      ["AA1234", "John Doe", "BB1234", "Jane Smith", "Mixed Doubles", "A", "Waitlisted"],
      ["BX1234", "Quang Do", "DG1234", "Ben Johns", "Mens Open", "B", "Not Payed"],
    ];

    $handle = fopen('php://temp', 'w+');

    // PHP 8.x requires explicit parameters to avoid deprecation notices
    // Parameters are: $handle, $fields, $separator, $enclosure, $escape
    fputcsv($handle, $headers, ",", "\"", "\\");

    foreach ($example_data as $row) {
      fputcsv($handle, $row, ",", "\"", "\\");
    }

    rewind($handle);
    $content = stream_get_contents($handle);
    fclose($handle);

    $response = new Response($content);
    $disposition = HeaderUtils::makeDisposition(
      HeaderUtils::DISPOSITION_ATTACHMENT,
      'pairings_template.csv'
    );

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
  }
}
