<?php 

class block_simplehtml extends block_base {
  public function init(){
    $this->title = get_string('simplehtml', 'block_simplehtml');
  }

  public function get_content() {
    if($this->content !== null) {
      return $this->content;
    }

    $this->content = new stdClass;
    $this->content->text = 'Simple block content';
    if(!empty($this->config->text)) {
      $this->content->text = $this->config->text;
    }
    $this->content->footer = 'footer';

    return $this->content;
  }

  public function specialization() {
    if(!empty($this->config->title)) {
      $this->title = $this->config->title;
    }
  }

  public function instance_config_save($data, $nolongerused = false) {
    if (get_config('simplehtml', 'Allow_HTML') == '1') {
      $data->text = strip_tags($data->text);
    }
    return parent::instance_config_save($data, $nolongerused);
  }

  public function instance_allow_multiple() {
    return true;
  }

  public function hide_header()
  {
    return true;
  }

  public function html_attributes() {
    $attributes = parent::html_attributes(); // Get default values
    $attributes['class'] .= ' block_' . $this->name(); // Append our class to class attribute
    return $attributes;
  }

  function has_config() {return true;}
}