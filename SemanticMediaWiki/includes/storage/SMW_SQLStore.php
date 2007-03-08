<?php
/**
 * SQL implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 */

global $smwgIP;
require_once( "$smwgIP/includes/storage/SMW_Store.php" );
require_once( "$smwgIP/includes/SMW_Datatype.php" );
require_once( "$smwgIP/includes/SMW_DataValue.php" );

/**
 * Storage access class for using the standard MediaWiki SQL database
 * for keeping semantic data.
 */
class SMWSQLStore extends SMWStore {

///// Reading methods /////

	function getSpecialValues(Title $subject, $specialprop, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       'AND property_id=' . $db->addQuotes($specialprop);
		$res = $db->select( $db->tableName('smw_specialprops'), 
		                    'value_string',
		                    $sql, 'SMW::getSpecialValues', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = $row->value_string;
			}
		}
		$db->freeResult($res);
		return $result;
	}


	function getAttributeValues(Title $subject, Title $attribute, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       ' AND attribute_title=' . $db->addQuotes($attribute->getDBkey());

		$res = $db->select( $db->tableName('smw_attributes'), 
		                    'value_unit, value_datatype, value_xsd',
		                    $sql, 'SMW::getAttributeValues', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValue::newTypedValue(SMWTypeHandlerFactory::getTypeHandlerByID($row->value_datatype));
				$dv->setXSDValue($row->value_xsd, $row->value_unit);
				$result[] = $dv;
			}
		}
		$db->freeResult($res);

		return $result;
	}


	function getAttributes(Title $subject, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID());

		$res = $db->select( $db->tableName('smw_attributes'), 
		                    'DISTINCT attribute_title',
		                    $sql, 'SMW::getAttributes', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getRelationObjects(Title $subject, Title $relation, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       ' AND relation_title=' . $db->addQuotes($relation->getDBKey());

		$res = $db->select( $db->tableName('smw_relations'), 
		                    'object_title, object_namespace',
		                    $sql, 'SMW::getRelationObjects', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->object_title, $row->object_namespace);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getRelationSubjects(Title $relation, Title $object, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'object_namespace=' . $db->addQuotes($object->getNamespace()) . 
		       ' AND object_title=' . $db->addQuotes($object->getDBKey()) .
		       ' AND relation_title=' . $db->addQuotes($relation->getDBKey());

		$res = $db->select( $db->tableName('smw_relations'), 
		                    'DISTINCT subject_id',
		                    $sql, 'SMW::getRelationSubjects', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromID($row->subject_id);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getOutRelations(Title $subject, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID());

		$res = $db->select( $db->tableName('smw_relations'), 
		                    'DISTINCT relation_title',
		                    $sql, 'SMW::getOutRelations', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->relation_title, SMW_NS_RELATION);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getInRelations(Title $object, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'object_namespace=' . $db->addQuotes($object->getNamespace()) . 
		       ' AND object_title=' . $db->addQuotes($object->getDBKey());

		$res = $db->select( $db->tableName('smw_relations'), 
		                    'DISTINCT relation_title',
		                    $sql, 'SMW::getInRelations', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->relation_title, SMW_NS_RELATION);
			}
		}
		$db->freeResult($res);

		return $result;
	}

///// Writing methods /////

	function deleteSubject(Title $subject) {
		$db =& wfGetDB( DB_MASTER );
		$db->delete($db->tableName('smw_relations'), 
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Relations');
		$db->delete($db->tableName('smw_attributes'), 
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Attributes');
		$db->delete($db->tableName('smw_specialprops'), 
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Specialprops');
	}

	function updateData(SMWSemData $data) {
		$db =& wfGetDB( DB_MASTER );
		$subject = $data->getSubject();
		$this->deleteSubject($subject);
		// relations
		foreach(SMWSemanticData::$semdata->getRelations() as $relation) {
			foreach(SMWSemanticData::$semdata->getRelationObjects($relation) as $object) {
				$db->insert( $db->tableName('smw_relations'),
				             array( 'subject_id' => $subject->getArticleID(),
				            'subject_namespace' => $subject->getNamespace(),
				            'subject_title' => $subject->getDBkey(),
				            'relation_title' => $relation->getDBkey(),
				            'object_namespace' => $object->getNamespace(),
				            'object_title' => $object->getDBkey()),
				            'SMW::updateRelData');
			}
		}

		//attributes
		foreach(SMWSemanticData::$semdata->getAttributes() as $attribute) {
			$attributeValueArray = SMWSemanticData::$semdata->getAttributeValues($attribute);
			foreach($attributeValueArray as $value) {
				// DEBUG echo "in storeAttributes, considering $value, getXSDValue=" . $value->getXSDValue() . "<br />\n" ;
				if ($value->getXSDValue()!==false) {
					$db->insert( $db->tableName('smw_attributes'),
					             array( 'subject_id' => $subject->getArticleID(),
					             'subject_namespace' => $subject->getNamespace(),
					             'subject_title' => $subject->getDBkey(),
					             'attribute_title' => $attribute->getDBkey(),
					             'value_unit' => $value->getUnit(),
					             'value_datatype' => $value->getTypeID(),
					             'value_xsd' => $value->getXSDValue(),
					             'value_num' => $value->getNumericValue()),
					             'SMW::updateAttData');
				}
			}
		}

		//special properties
		foreach (SMWSemanticData::$semdata->getSpecialProperties() as $special) {
			if ($special == SMW_SP_IMPORTED_FROM) { // don't store this, just used for display; TODO: filtering it here is bad
				continue;
			}
			$valueArray = SMWSemanticData::$semdata->getSpecialValues($special);
			foreach($valueArray as $value) {
				if ($value instanceof SMWDataValue) {
					if ($value->getXSDValue() !== false) { // filters out error-values etc.
						$stringvalue = $value->getXSDValue();
					}
				} elseif ($value instanceof Title) {
					if ( $special == SMW_SP_HAS_TYPE ) { // special handling, TODO: change this to use type ids
						$stringvalue = $value->getText();
					} else {
						$stringvalue = $value->getPrefixedText();
					}
				} else {
					$stringvalue = $value;
				}
				$db->insert( $db->tableName('smw_specialprops'),
				             array('subject_id' => $subject->getArticleID(),
				                   'subject_namespace' => $subject->getNamespace(),
				                   'subject_title' => $subject->getDBkey(),
				                   'property_id' => $special,
				                   'value_string' => $stringvalue), 
				             'SMW::updateSpecData');
			}
		}
	}

///// Private methods /////

	/**
	 * Transform input parameters into a suitable array of SQL options.
	 */
	protected function getSQLOptions($limit, $offset) {
		$sql_options = array();
		if ($limit >= 0) {
			$sql_options['LIMIT'] = $limit;
		}
		if ($offset > 0) {
			$sql_options['OFFSET'] = $offset;
		}
		return $sql_options;
	}

}

 
?>