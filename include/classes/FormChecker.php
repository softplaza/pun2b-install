<?php 
/**
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package FormChecker
**/

class FormChecker
{
	//Param
	public $formId = 0;
	public $formArr = array();
	
	// NEW
	public function setFormId($id)
	{
		$this->formId = $id;
	}
	
	public function setForm($form_array)
	{
		$this->formArr = $form_array;
	}
	
	// Check numeric elements. Default value = 0
	function check_elements($allowed_elements)
	{
		$form_output = array();
		$form_id = $this->formId;
		$form = !empty($this->formArr) ? $this->formArr : $_POST;

		foreach ($allowed_elements as $element)
		{
			if ($form_id > 0)
			{
				if (isset($form[$element][$form_id]))
					$form_output[$element] = $form[$element][$form_id];
				else
					$form_output[$element] = '';
			} 
			else
			{
				if (isset($form[$element]))
					$form_output[$element] = $form[$element];
				else
					$form_output[$element] = '';
			}
		}

		return $form_output;
	}
	
	function trim_arr($elements)
	{
		$charlist = " \t\n\r\0\x0B\xC2\xA0";
		$form_input = $new_form = array();
		
		$form_input = $this->check_elements($elements);
		
		if (!empty($form_input))
		{
			foreach($form_input as $key => $val)
			{
				if (in_array($key, $elements)) {
					$new_form[$key] = utf8_trim($val, $charlist);
				}
			}
		}
		
		return $new_form;
	}
	
	function intval_arr($elements)
	{
		$form_input = $new_form = array();
		
		$form_input = $this->check_elements($elements);
		
		if (!empty($form_input))
		{
			foreach($form_input as $key => $val)
			{
				if (in_array($key, $elements)) {
					$val = is_numeric($val) ? intval($val) : 0;
					$new_form[$key] = $val;
				}
			}
		}
		
		return $new_form;
	}
	
	function strtotime_arr($elements)
	{
		$form_input = $new_form = array();
		
		$form_input = $this->check_elements($elements);
		
		if (!empty($form_input))
		{
			foreach($form_input as $key => $val)
			{
				if (in_array($key, $elements)) {
					$val = ($val != '') ? strtotime($val) : 0;
					$new_form[$key] = $val;
				}
			}
		}
		
		return $new_form;
	}
}

