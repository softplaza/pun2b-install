<?php

class Messenger
{
	function delete_empty_topics()
	{
		global $DBLayer;
		
		// check empty conversations
		$query = array(
			'SELECT'	=> 't.id',
			'FROM'		=> 'sm_messenger_topics AS t',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'sm_messenger_users AS u',
					'ON'			=> 't.id=u.topic_id'
				)
			),
			'WHERE'		=> 'u.topic_id IS NULL'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$empty_ids = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			$empty_ids[] = $row['id'];
		}
		
		if (!empty($empty_ids))
		{
			$query = array(
				'DELETE'	=> 'sm_messenger_topics',
				'WHERE'		=> 'id IN('.implode(',', $empty_ids).')'
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$query = array(
				'DELETE'	=> 'sm_messenger_posts',
				'WHERE'		=> 'p_topic_id IN('.implode(',', $empty_ids).')'
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}
}
