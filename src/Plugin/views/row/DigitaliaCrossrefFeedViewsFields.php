<?php

namespace Drupal\digitalia_muni_crossref_xml\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Url;
use Drupal\xmlfeedviews\Plugin\views\row\XmlFeedViewsFields;


/**
 * Renders an XML FEED VIEWS item based on fields.
 *
 * @ViewsRow(
 *   id = "digitaliacrossreffeedviews_fields",
 *   title = @Translation("Digitalia Crossref Feed Views fields"),
 *   help = @Translation("Display fields as XML items."),
 *   theme = "xmlfeedviews_row",
 *   display_types = {"feed"}
 * )
 */
class DigitaliaCrossrefFeedViewsFields extends XmlFeedViewsFields {

  /**
   * Render.
   *
   * @param object $row
   *   Row object.
   *
   * @return array|string
   *   Returns array or string.
   */
  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }

    $item = new \stdClass();
    $item->body_before = isset($this->options['xmlfeedviews_body_before']) ? $this->options['xmlfeedviews_body_before'] : NULL;
    $item->body = $this->getBodyField($row_index, $this->options['xmlfeedviews_body']);
    $item->body_after = isset($this->options['xmlfeedviews_body_after']) ? $this->options['xmlfeedviews_body_after'] : NULL;

    $row_index++;

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    ];

    $body = $item->body;
    $article_id = trim(explode('nid###', explode('###nid', $body)[1])[0]);
    $body = str_replace('###nid'.$article_id.'nid###', '', $body);
    $article = \Drupal\node\Entity\Node::load($article_id); 
    $pages_field = $article->get('field_pagination')->getValue();
    if (!empty($pages_field)) {
      $from = $pages_field[0]['first'];
      $to = $pages_field[0]['second'] ? $pages_field[0]['second'] : $from;
      $pages = '<first_page>'.$from.'</first_page><last_page>'.$to.'</last_page>';
    } else {
      $pages = '<first_page/><last_page/>';
    }
    $body = str_replace('###pages###', $pages, $body);

    $handle = $article->get('field_handle')->getValue()[0]['value'];
    //$handle = trim($handle, 'digilib.');
    $link = 'https://digilib.phil.muni.cz/handle/11222.digilib/'.$handle;
    $body = str_replace('###link###', $link, $body);

    $authors = $this->getAuthors($article);
    $body = str_replace('###authors###', $authors, $body);

    //dpm(htmlspecialchars($body));
    $item->body = $body;
    return $build;
  }

  public function getAuthors($article) {
    $authors = $article->get('field_author')->getValue();
    $result = '';
    if (empty($authors)) {
      return $result;
    }
    foreach ($authors as $key => $item) {
      $author = \Drupal\node\Entity\Node::load($item['target_id']);
      $seq = $key == '0' ? 'first' : 'additional';
      if ($author !== NULL) {
        $name = $author->get('field_name_structured')->getValue();
        $author_id = $author->get('field_author_id')->getValue()[0]['target_id'];
        $given = $name[0]['given'];
        $family = $name[0]['family'];
        $result = $result.'<person_name sequence="'.$seq.'" contributor_role="author"><given_name>'.$given.'</given_name>';
        $result = $result.'<surname>'.$family.'</surname></person_name>';
      }
    }

    return $result;
  }
}
