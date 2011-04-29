<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Copyright (c) 2011, Doron Horwitz
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * * The name of Doron Horwitz may not be used to endorse or promote products
 *   derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * 
 * A CodeIgniter library for simplifying the task of generating XML for the 
 * XML/SWF Charts library found at {@link http://maani.us/xml_charts/}
 * 
 * This CodeIgniter library has a method corresponding to each of the base XML
 * nodes which are listed in the {@link http://maani.us/xml_charts/index.php?menu=Reference XML/SWF Charts reference}.
 * By calling the relevant methods a XML DOM document is built up. When the XML has to be displayed, a call
 * to {@link Maani_xml_swf_charts::generate_xml() generate_xml()} returns the XML as a string.
 * In this way, the library hides the complexity of manually building up a DOM document to generate this XML.
 * The {@link Maani_xml_swf_charts::__call() __call()} magic method is used to group the various nodes into different types depending on
 * on their structure required by the XML/SWF Charts library.
 *
 * @package xml_swf_charts_for_ci
 * @license http://doronhorwitz.github.com/bsd_new_license.txt New BSD License
 * @see http://doronhorwitz.github.com/XML-SWF-Charts-for-CI/
 * @see http://maani.us/xml_charts/
 * @author Doron Horwitz <milktekza@gmail.com>
 * @version 1.0
 * @copyright Copyright (c) 2011, Doron Horwitz
 */

/**
 * The main class on which methods are called to add nodes to the XML document
 * 
 * This is the main class which can be manipulated from a CodeIgniter controller
 * to add nodes one at a time to the XML document which will eventually be generated.
 * 
 * @package xml_swf_charts_for_ci
 * @subpackage classes
 * @author Doron Horwitz <milktekza@gmail.com>
 * @version 1.0
 * @copyright Copyright (c) 2011, Doron Horwitz
 */
class Maani_xml_swf_charts {
	
	/**
	 * Stores the types for each of the nodes.
	 * 
	 * This variable helps the {@link __call()} magic method determine the type
	 * of the node whose corresponding method was called on the library.
	 * 
	 * @access private
	 * @var array
	 */
	var $_node_types = array (
		"Data_node"=>array(
			"chart_data"),
		"Self_closing_anode"=>array(
			"chart_border","chart_grid_h","chart_grid_v","chart_guide","chart_label",
			"chart_note","chart_pref","chart_rect","chart_transition","series",
			"axis_category","axis_ticks","axis_value","context_menu","legend",
			"link_data","scroll","tooltip","update"),
		"Node_with_same_text_nodes"=>array(
			"chart_type","series_color","series_explode","axis_category_label","axis_value_label"),
		"Node_with_same_anodes"=>array(
			"link"),
		"Node_with_diff_text_anodes"=>array(
			"draw","filter"),
		"ANode_with_same_text_nodes"=>array(
			"embed"));

	/**
	 * Some of the base nodes require child nodes. This array stores the names of
	 * these nodes.
	 * 
	 * @access private
	 * @var array 
	 */
	var $_sub_node_names = array (
		"chart_type"=>"string",
		"series_color"=>"color",
		"series_explode"=>"number",
		"axis_category_label"=>"string",
		"axis_value_label"=>"string",
		"link"=>"area",
		"embed"=>"font");

	/**
	 * A store of the XML nodes (each represented by a DOMElement) whose
	 * corresponding method names have been called.
	 * 
	 * Nodes are stored in an associative manner where the name of the node is
	 * the index of the DOMElement. This array does not store more than one
	 * instance of a node for each node type.
	 * 
	 * @access private
	 * @var array
	 */
	var $_nodes = array();

	/**
	 * Constructor
	 */
	function __construct() {		
	}

	/**
	 * This magic method generates the DOMElement for the corresponding method
	 * called on the library.
	 * 
	 * @param string $name the name of the method being called
	 * @param array $arguments the arguments passed into the method
	 */
	function __call($name, $arguments) {
		$userdata = new stdClass();
		$userdata->pos = null;
		$userdata->search_item = $name;
		array_walk($this->_node_types,array($this,"_search_for_type"),$userdata);

		$node_obj = null;
		if ($userdata->pos !== null) {
			switch ($userdata->pos) {
				case "Data_node":
					$arguments[3] = (isset($arguments[3]))?$arguments[3]:array();
					$this->_nodes[$name] = new Data_node($arguments[0],$arguments[1],$arguments[2],$arguments[3]);
					break;
				case "Self_closing_anode":
					$this->_nodes[$name] = new Self_closing_anode($name,$arguments[0]);
					break;
				case "Node_with_same_text_nodes":
					$this->_nodes[$name] = new Node_with_same_text_nodes($name,$this->_sub_node_names[$name],$arguments[0]);
					break;
				case "Node_with_same_anodes":
					$this->_nodes[$name] = new Node_with_same_anodes($name,$this->_sub_node_names[$name],$arguments[0]);
					break;
				case "Node_with_diff_text_anodes":
					$this->_nodes[$name] = new Node_with_diff_text_anodes($name,$arguments[0]);
					break;
				case "ANode_with_same_text_nodes":
					$this->_nodes[$name] = new ANode_with_same_text_nodes($name,$this->_sub_node_names[$name],$arguments[0],$arguments[1]);
					break;
				default:
					break;
			}
		}
	}

	/**
	 * A utility method used by the {@link __call()} method for determining
	 * the type of a node.
	 *
	 * @param array $value the array through which to search for the value
	 * @param int $key the key of the position in which the value was found
	 * @param object $userdata an object containing the value being searched for
	 * and which will contain the position of the found element after the array_walk()
	 * has been in the {@link __call()}
	 * @access private
	 */
	function _search_for_type($value,$key,$userdata) {
		if (in_array($userdata->search_item,$value)) {
			$userdata->pos = $key;
		}
	}

	/**
	 * Generate the XML document string.
	 * 
	 * This method iterates through each of the nodes stored in {@link $_nodes}
	 * and calls each of their generate_xml() methods to build up the XML document.
	 * 
	 * @param bool $format_xml indicates if the XML should be printed prettily (true) or not (false). 
	 * @return string a string containing the XML
	 */
	function generate_xml($format_xml=false) {
		$dom_document = new DomDocument();
		$dom_document->formatOutput = (is_bool($format_xml) && ($format_xml==true))?$format_xml:false;
		$chart_node = $dom_document->createElement('chart');

		foreach($this->_nodes as $node) {
			$chart_node->appendChild($node->generate_xml($dom_document));
		}

		$dom_document->appendChild($chart_node);
		return $dom_document->saveXML();
	}

	/**
	 * Clear out any nodes added to the DOM document.
	 * 
	 * This can be used to allow the library to produce more than one XML document
	 * in the same script.
	 */
	function clear() {
		$this->_nodes = array();
	}
}

/**
 * This represents the <chart_data> XML node.
 * 
 * The <number> subnode can include attributes to individually format individual
 * data points. This method allows these attributes to be included n one of three ways:
 * 1) included in the same array as the data (each data element is represented by
 * an associative array with a "val" element [the value] and an "attrs" element [an
 * array of attributes]
 * 2) included in a separate array with only one set of attributes. These attributes
 * will be applied to all <number> subnodes.
 * 3) included in a separate array with rows and columns of arrays to be included
 * only with the same data node located at the same position in the data array
 * 
 * If there are duplicate attributes the precedence is:
 * 1 then 3 then 2
 * 
 * @package xml_swf_charts_for_ci
 * @subpackage classes
 * @author Doron Horwitz <milktekza@gmail.com>
 * @version 1.0
 * @copyright Copyright (c) 2011, Doron Horwitz
 */
class Data_node {
	
	/**
	 * The array of labels on the independent axis (which is usually the horizontal axis)
	 * 
	 * @access private
	 * @var array
	 */
	var $_category_headers;
	
	/**
	 * The array of labels on the dependent axis (which is usually the vertical axis)
	 * 
	 * @access private
	 * @var array 
	 */
	var $_value_headers;
	
	/**
	 * The actual numeric values to be plotted
	 * 
	 * @access private
	 * @var array
	 */
	var $_data;
	
	/**
	 * Attributes to be added to the <number> subnodes
	 * 
	 * @access private
	 * @var array
	 */
	var $_attrs;

	/**
	 * Constructor
	 * 
	 * @param array $category_headers the array of labels on the independent axis (which is usually the horizontal axis)
	 * @param array $value_headers the array of labels on the dependent axis (which is usually the vertical axis)
	 * @param array $data 2D array of data representing rows and columns. The data can also contain attributes
	 * @param array $attrs separate array of attributes. Either a single dimensional array of key/value pairs for attributes
	 * or a 2D array of associative arrays at the same positions of the relevant data in the $data.
	 */
	function __construct($category_headers,$value_headers,$data,$attrs=array()) {
		$this->_category_headers = $category_headers;
		$this->_value_headers = $value_headers;
		$this->_data = $data;
		$this->_attrs = is_array($attrs)?$attrs:array();
	}


	/**
	 * Create the DOMElement representing the <chart_data> node
	 * 
	 * @param DOMDocument $dom_document the DOMDocument object from which DOMElement are created
	 * @return DOMElement
	 */
	function generate_xml($dom_document) {
		$node = $dom_document->createElement('chart_data');

		$row_node = $dom_document->createElement('row');
		$row_node->appendChild($dom_document->createElement('null'));
		foreach ($this->_category_headers as $category_header) {
			$row_node->appendChild($dom_document->createElement('string',$category_header));
		}
		$node->appendChild($row_node);

		//get the dimensons of the data
		$num_data_rows = count($this->_data);
		$num_data_cols = count($this->_data[0]); //assumes balanced array

		$global_attrs = array();

		//if $this->_attrs is not separate (ie it is global),
		//then set it to $global_attrs and empty out $this->_attrs
		if (!((!empty($this->_attrs) && is_array(current($this->_attrs))))) { //checks if $this->_attrs is not separate
			$global_attrs = $this->_attrs;
			$this->_attrs = array();
		}

		//pad $this->_attrs to the dimensions of the data
		$empty_row = array_fill(0,$num_data_cols,array());
		foreach ($this->_attrs as &$attrs_row) { //take note of the "&": passed into foreach by reference
			$attrs_row = array_pad($attrs_row,$num_data_cols,array());
		}
		$this->_attrs = array_pad($this->_attrs,$num_data_rows,$empty_row);


		//iterate through the data rows
		foreach ($this->_data as $row_num=>$data_row) {
			//create a new row node
			$row_node = $dom_document->createElement('row');
			//append the value header for that row
			$value_node = $dom_document->createElement('string');
			if (!empty($this->_value_headers[$row_num])){
				$value_node->appendChild($dom_document->createTextNode($this->_value_headers[$row_num]));
			}
			$row_node->appendChild($value_node);

			//iterate through data elements in the row
			foreach ($data_row as $col_num=>$datum) {
				//create a new number node
				$data_node = $dom_document->createElement('number');
				//$attrs_to_apply is the attributes that will be applied to the node
				//initialise it to the $global_attrs
				$attrs_to_apply = $global_attrs;
				//assign the separate attrs ($this->_attrs) to be the $attrs_to_apply
				$attrs_to_apply = array_merge($attrs_to_apply,$this->_attrs[$row_num][$col_num]);
				//if the datum has attributes with it
				if (is_array($datum)) {
					//assign the attributes with the datum to $attrs_to_apply
					$attrs_to_apply = array_merge($attrs_to_apply,$datum["attrs"]);
					//create the text for the node based on the "val" element
					$data_node->appendChild($dom_document->createTextNode($datum["val"]));
				
				//if the datum has no attributes with it
				} else {
					$data_node->appendChild($dom_document->createTextNode($datum));
				}

				//apply the attributes
				foreach ($attrs_to_apply as $attr_name=>$attr_val) {
					$data_node->setAttribute($attr_name,$attr_val);
				}
				
				//append the data node to the row node
				$row_node->appendChild($data_node);
			}
			$node->appendChild($row_node);
		}

		return $node;

		/*//individual separate attrs
		//@todo: redo this using array_walk
		if (!empty($this->_attrs) && is_array($this->_attrs) && is_array(current($this->_attrs))) {
			foreach ($this->_attrs as $row_num=>$attr_row) {
				foreach ($attr_row as $col_num=>$attrs) {
					if (isset($this->_data[$row_num][$col_num])) {
						if (!is_array($this->_data[$row_num][$col_num])) {
							$this->_data[$row_num][$col_num] = array("val"=>$this->_data[$row_num][$col_num]);
						}
						if (!isset($this->_data[$row_num][$col_num]["attrs"])) {
							$this->_data[$row_num][$col_num]["attrs"] = array(); //4th dimension!!!
						}
						$this->_data[$row_num][$col_num]["attrs"]=array_merge($this->_data[$row_num][$col_num]["attrs"],$attrs);

					}
				}
			}
		}

		//FIX!
		foreach ($this->_data as $i=>$data_row) {
			$sub_node = $dom_document->createElement('row');
			$sub_node->appendChild($dom_document->createElement('string',$this->_value_headers[$i]));
			foreach($data_row as $datum) {
				$number_node = $dom_document->createElement('number');
				foreach ($this->_attrs as $attr)
					if  (is_array($datum)) {
						$number_node->appendChild($dom_document->createTextNode($datum["val"]));
						foreach ($datum["attrs"] as $attr_name=>$attr_val) {
							$number_node->setAttribute($attr_name,$attr_val);
						}
				
					} else {
						$number_node->appendChild($dom_document->createTextNode($datum));
						foreach ($datum["attrs"] as $attr_name=>$attr_val) {
							$number_node->setAttribute($attr_name,$attr_val);
					}
				}
				$sub_node->appendChild($number_node);
			}
			$node->appendChild($sub_node);
		}

		return $node;*/
	}
}

/**
 * Represents a self-closing XML node with attributes
 * 
 * e.g.
 * <node attrname1="attrval1" attrname2="attrval2" />
 * 
 * @author Doron Horwitz <milktekza@gmail.com>
 * @version 1.0
 * @copyright Copyright (c) 2011, Doron Horwitz 
 * @package xml_swf_charts_for_ci
 * @subpackage classes
 */
class Self_closing_anode {
	/**
	 * The name of the node
	 * 
	 * @var string
	 * @access private
	 */
	var $_node_name;
	
	/**
	 * The attributes to be included with the node
	 * 
	 * @var type
	 * @access private
	 */
	var $_attrs;

	/**
	 * Consructor
	 * 
	 * @param string $node_name the name of the node
	 * @param array $attrs an associative array of keys/values represents attribute names/values
	 */
	function __construct($node_name,$attrs) {
		$this->_node_name = $node_name;
		$this->_attrs = $attrs;
	}

	/**
	 * Create the DOMElement representing the node
	 * 
	 * @param DOMDocument $dom_document the DOMDocument object from which DOMElement are created
	 * @return DOMElement
	 */
	function generate_xml($dom_document) {
		$node = $dom_document->createElement($this->_node_name);
		foreach($this->_attrs as $attr_name=>$attr_val) {
			$node->setAttribute($attr_name,$attr_val);
		}
		return $node;
	}
}

/**
 * Represents a node which has text subnodes (all with the same name). Subnode
 * can also be <null />
 * 
 * e.g.
 * <node>
 *  <subnode>Example1</subnode>
 *  <subnode>Example2</subnode>
 *  </null>
 *  <subnode>Example3</subnode>
 * </node>
 *
 * @author Doron Horwitz <milktekza@gmail.com>
 * @version 1.0
 * @copyright Copyright (c) 2011, Doron Horwitz
 * @package xml_swf_charts_for_ci
 * @subpackage classes
 */
class Node_with_same_text_nodes {
	/**
	 * The name of the node
	 * 
	 * @var string 
	 * @access private
	 */
	var $_node_name;
	
	/**
	 * The name of the text subnode
	 * 
	 * @var string
	 * @access private
	 */
	var $_text_node_name;
	
	/**
	 * A numerically indexed array of text for each of the text subnodes
	 * 
	 * @var array
	 * @access private
	 */
	var $_data;

	/**
	 * Constructor
	 * 
	 * @param string $node_name the name of the node
	 * @param string $text_node_name the name of the subnodes
	 * @param array $data the text to be in each subnode
	 */
	function __construct($node_name,$text_node_name,$data) {
		$this->_node_name = $node_name;
		$this->_text_node_name = $text_node_name;
		$this->_data = $data;
	}

	/**
	 * Create the DOMElement representing the node
	 * 
	 * @param DOMDocument $dom_document the DOMDocument object from which DOMElement are created
	 * @return DOMElement
	 */
	function generate_xml($dom_document) {
		$node = null;
		if (is_array($this->_data)) {
			$node = $dom_document->createElement($this->_node_name);
			foreach ($this->_data as $datum) {
				if (is_null($datum)) {
					$text_node = $dom_document->createElement("null");
				} else {
					$text_node = $dom_document->createElement($this->_text_node_name,$datum);
				}
				$node->appendChild($text_node);
			}
		} else {
			$node = $dom_document->createElement($this->_node_name,$this->_data);
		}

		return $node;
	}
}

/**
 * Represents a node containing self-closing subnodes all with the same name.
 * Each of these subnodes can have its own set of attributes.
 * 
 * e.g.
 * <node>
 *  <subnode attrname1="attrval1" attrname2="attrval2" attrname3="attrval3" />
 *  <subnode attrname4="attrval4" attrname5="attrval5" />
 * </node>
 * 
 * @author Doron Horwitz <milktekza@gmail.com>
 * @version 1.0
 * @copyright Copyright (c) 2011, Doron Horwitz 
 * @package xml_swf_charts_for_ci
 * @subpackage classes
 */
class Node_with_same_anodes {
	/**
	 * The name of the node
	 * 
	 * @var string
	 * @access private
	 */
	var $_node_name;
	
	/**
	 * The name of self-closing nodes
	 * 
	 * @var string
	 * @access private 
	 */
	var $_sub_node_name;
	
	/**
	 * An numerically indexed array of associative arrays. Each associative array
	 * contains the attributes for each of the subnodes. The keys/values of the 
	 * associative arrays represent the names/values of the attributes.
	 * 
	 * @var array
	 * @access private
	 */
	var $_data;

	/**
	 * Constructor
	 * 
	 * @param type $node_name the name of the node
	 * @param type $sub_node_name the name for all the subnodes
	 * @param type $data an array of associative arrays of names/values for attributes for each node
	 */
	function __construct($node_name,$sub_node_name,$data) {
		$this->_node_name = $node_name;
		$this->_sub_node_name = $sub_node_name;
		$this->_data = $data;
	}

	/**
	 * Create the DOMElement representing the node
	 * 
	 * @param DOMDocument $dom_document the DOMDocument object from which DOMElement are created
	 * @return DOMElement
	 */
	function generate_xml($dom_document) {
		$node = $dom_document->createElement($this->_node_name);
		foreach ($this->_data as $datum) {
			$sub_node = $dom_document->createElement($this->_sub_node_name);
			foreach ($datum as $attr_name=>$attr_val) {
				$sub_node->setAttribute($attr_name,$attr_val);
			}
			$node->appendChild($sub_node);
		}
		return $node;
	}
}

/**
 * Represents a node with text subnodes with different names. Each of the text
 * subnodes can have attributes.
 * 
 * e.g.
 * <node attrname1="attrval1" attrname2="attrval2">
 *  <subnode1 attrname3="attrval3" attrname4="attrval4" attrname5="attrval5">Example1</subnode1>
 *  <subnode2 attrname3="attrval3" attrname4="attrval4">Example2</subnode2>
 *  <subnode3 attrname5="attrval5" attrname5="attrval5 />
 * </node>
 *
 * @author Doron Horwitz <milktekza@gmail.com>
 * @version 1.0
 * @copyright Copyright (c) 2011, Doron Horwitz
 * @package xml_swf_charts_for_ci
 * @subpackage classes
 */
class Node_with_diff_text_anodes {
	/**
	 * The name of the node
	 * 
	 * @var string
	 * @access private
	 */
	var $_node_name;
	
	/**
	 * A store of the names and attributes of each of the subnodes. This array
	 * is numerically indexed with each element being an associative array containing
	 * the names/values of the attributes. Two associative indices are reserved for
	 * storing the subnode name and the text within the subnode
	 * 1) "_type" is name of the subnode
	 * 2) "_text" is text contained in the subnode - this does not have to exist
	 * as the node doesn't have to have text
	 * 
	 * @var array
	 * @access private
	 */
	var $_data;

	/**
	 * Constructor
	 * 
	 * @param string $node_name the name of the node
	 * @param array $data an array of associative arrays containing names/values of attributes
	 * for each subnode and also the names and text of these subnodes.
	 */
	function __construct($node_name,$data) {
		$this->_node_name = $node_name;
		$this->_data = $data;
	}

	/**
	 * Create the DOMElement representing the node
	 * 
	 * @param DOMDocument $dom_document the DOMDocument object from which DOMElement are created
	 * @return DOMElement
	 */
	function generate_xml($dom_document) {
		$node = $dom_document->createElement($this->_node_name);
		foreach($this->_data as $datum) {
			$sub_node = null;
			//@todo should assert a check here to ensure that a "_type" element is available
			if (isset($datum["_text"])) {
				$sub_node = $dom_document->createElement($datum["_type"],$datum["_text"]);
			} else {
				$sub_node = $dom_document->createElement($datum["_type"]);
			}
			foreach ($datum as $attr_name=>$attr_val) {
				if (substr($attr_name,0,1) != "_") {
					$sub_node->setAttribute($attr_name,$attr_val);
				}
			}
			$node->appendChild($sub_node);
		}
		return $node;
	}
}

/**
 * Represents a node with text subnodes. The text subnodes all have the same
 * name. Both the node can have attributes.
 * 
 * e.g.
 * <node attrname1="attrval1" attrname2="attrval2">
 *  <subnode>Example1</subnode>
 *  <subnode>Example2</subnode>
 * </node>
 * 
 * @package xml_swf_charts_for_ci
 * @subpackage classes
 * @author Doron Horwitz <milktekza@gmail.com>
 * @version 1.0
 * @copyright Copyright (c) 2011, Doron Horwitz
 */
class ANode_with_same_text_nodes {
	/**
	 * The name of the node
	 * 
	 * @var string
	 * @access private
	 */
	var $_node_name;
	
	/**
	 * The name of the subnodes
	 * 
	 * @var string
	 * @access private
	 */
	var $_sub_node_name;
	
	/**
	 * A numerically indexed array of the text contained in each node
	 * 
	 * @var array 
	 * @access private
	 */
	var $_data;
	
	/**
	 * An associative array of name/values for each attribute for the main node
	 * @var array 
	 * @access private
	 */
	var $_attrs;

	/**
	 * Constructor
	 * 
	 * @todo: make $attrs optional (requires changing the switch statement in Maani_xml_swf_charts->__call())
	 * @param string $node_name the name of the node
	 * @param string $sub_node_name the name for each subnode
	 * @param array $data a numerically indexed array of text for each subnode
	 * @param array $attrs an associative array of names/values of the attributes for the main node
	 */
	function __construct($node_name,$sub_node_name,$data,$attrs) {
		$this->_node_name = $node_name;
		$this->_sub_node_name = $sub_node_name;
		$this->_data = $data;
		$this->_attrs = $attrs;
	}

	/**
	 * Create the DOMElement representing the node
	 * 
	 * @param DOMDocument $dom_document the DOMDocument object from which DOMElement are created
	 * @return DOMElement
	 */
	function generate_xml($dom_document) {
		$node = $dom_document->createElement($this->_node_name);
		foreach($this->_attrs as $attr_name=>$attr_val) {
			$node->setAttribute($attr_name,$attr_val);
		}

		foreach($this->_data as $datum) {
			$sub_node = $dom_document->createElement($this->_sub_node_name,$datum);
			$node->appendChild($sub_node);
		}
		return $node;
	}
}

?>
