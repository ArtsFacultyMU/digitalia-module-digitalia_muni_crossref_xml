<?php

namespace Drupal\digitalia_muni_crossref_xml\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\xmlfeedviews\Plugin\views\style\XmlFeedViews;

/**
 * Default style plugin to render an OPML feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "digitaliacrossreffeedviews",
 *   title = @Translation("Digitalia Crossref Feed Views"),
 *   help = @Translation("Generates an XML feed from a view."),
 *   theme = "xmlfeedviews",
 *   display_types = {"feed"}
 * )
 */
class DigitaliaCrossrefFeedViews extends XmlFeedViews {

  /**
   * Get XML feed views header.
   *
   * @return string
   *   The string containing the description with the tokens replaced.
   */
  public function getHeader() {
    $header = $this->options['xmlfeedviews_head'];

    // replace tokens
    $issue = $this->tokenizeValue("{{ field_member_of }}", 0);
    $volume = $this->tokenizeValue("{{ field_member_of_1 }}", 0);
    $year = $this->tokenizeValue("{{ field_publication_year }}", 0);
    $year_actual = $this->tokenizeValue("{{ field_publication_year_actual }}", 0);

    $date = $this->tokenizeValue("{{ field_publication_date }}", 0);
    $serial = $this->tokenizeValue("{{ field_member_of_2 }}", 0);
    $issue_id = $this->tokenizeValue("{{ nid_1 }}", 0);
    if (!$issue_id) {
      \Drupal::logger('digitalia_muni_crossref_xml')->notice('Crossref XML: missing issue id in view.');
      return $header;
    }

    $published = $this->tokenizeValue("{{ status_1 }}", 0);
    //replace with values
    $header = str_replace("{{ field_member_of }}", $issue, $header);
    $header = str_replace("{{ field_member_of_1 }}", $volume, $header);
    $header = str_replace("{{ field_member_of_2 }}", $serial, $header);
    $header = str_replace("{{ field_publication_year }}", $year, $header);
    $header = str_replace("{{ field_publication_year_actual }}", $year_actual, $header);
    $header = str_replace("{{ field_publication_date }}", $date, $header);
    $header = str_replace("{{ nid_1 }}", $issue_id, $header);
    $header = str_replace("{{ status_1 }}", $published, $header);

    $serial_id = $this->tokenizeValue("{{ nid_2 }}", 0);
    if (!$serial_id) {
      \Drupal::logger('digitalia_muni_crossref_xml')->notice('Crossref XML: missing serial id in view.');
      return $header;
    }
    $serial = \Drupal\node\Entity\Node::load($serial_id);
    $issn_online = $this->getIssn($serial, true);
    $issn_print = $this->getIssn($serial, false);

    if (!empty($issn_online)) {
      $header = str_replace("###issn_online###", '<issn media_type="electronic">'.$issn_online.'</issn>', $header);
    } else {
      $header = str_replace("###issn_online###", '', $header);
    }

    if (!empty($issn_print)) {
      $header = str_replace("###issn_print###", '<issn media_type="print">'.$issn_print.'</issn>', $header);
    } else {
      $header = str_replace("###issn_print###", '', $header);
    }

    $params = 'xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1" xmlns:ai="http://www.crossref.org/AccessIndicators.xsd"';
    $header = str_replace("###params###", $params, $header);
    $registrant = '<registrant>Masarykova univerzita</registrant>';
    $header = str_replace("###registrant###", $registrant, $header);

    // get issue
    $serial_lang = $this->getSerialLang($serial);

    $header = str_replace("###serial_lang###", $serial_lang, $header);

    $timestamp = time();
    $batch = $year.'-'.$issue.'-'.$timestamp;

    $header = str_replace("###timestamp###", $timestamp, $header);
    $header = str_replace("###batch_id###", $batch, $header);

    return $header;
  }

  public function getSerialLang($serial) {
    if ($serial->getTitle() == "Archaeologia historica") {
      return 'cs';
    }
 
    $languages = $serial->get('field_language')->getValue();

    $codes = array("English" => "en", "Russian" => "ru", "Czech" => "cs", "Italian" => "it");
    if (!empty($languages)) {
      $tid = $languages[0]['target_id'];
      $term = \Drupal\taxonomy\Entity\Term::load($tid);
      $lang = $term->getName();
      return $codes[$lang];
    }
    return '';
  }
  public function getIssn($serial, $online) {
    $issn_field = $serial->get('field_issn')->getValue();
    foreach ($issn_field as $item) {
      $value = $item['value'];
      if ($online and str_contains($value, 'online')) {
        return trim(str_replace('(online)', '', $value)); 
      }

      if (!$online and str_contains($value, 'print')) {
        return trim(str_replace('(print)', '', $value));
      }
    }
    return '';
  }
}
